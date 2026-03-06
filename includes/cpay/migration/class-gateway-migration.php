<?php
/**
 * Gateway Migration System
 * 
 * Handles migration of subscriptions from various payment gateways to ConvesioPay
 * Currently supports: Stripe, Authorize.net
 *
 * @author ConvesioPay
 */

namespace ConvesioPay;

defined('ABSPATH') || exit;

class Gateway_Migration {

    /**
     * Migration status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_REVERTED = 'reverted';

    /**
     * Add a source_gateway property for future extensibility
     */
    public static $default_source_gateway = 'stripe';

    /**
     * Supported source gateways
     */
    public static $supported_source_gateways = [
        'stripe' => [
            'name' => 'Stripe',
            'gateway_ids' => [
                'stripe',
                'stripe_cc',
                'stripe_credit_card',
                'woocommerce_stripe',
                'woocommerce_stripe_cc',
                'woosa_adyen_credit_card',
                'adyen_credit_card',
                'adyen_cc'
            ],
            'scan_method' => 'scan_subscriptions'
        ],
        'authorizenet' => [
            'name' => 'Authorize.net',
            'gateway_ids' => [
                'authorize_net_cim_credit_card',
                'authorize_net_credit_card',
                'authorize_net_echeck',
                'wc_authorize_net_cim_credit_card',
                'wc_authorize_net_credit_card',
                'wc_authorize_net_echeck',
                'skyverge_authorize_net_cim_credit_card',
                'skyverge_authorize_net_credit_card',
                'authorize_net_aim',
                'authorize_net_dpm',
                'authorize_net_sim'
            ],
            'scan_method' => 'scan_authorizenet_subscriptions'
        ]
    ];

    /**
     * Initialize the migration system
     */
    public static function init() {
        // Register the migration page as a hidden submenu so it is routable but not visible in the menu
        add_action('admin_menu', function() {
            add_submenu_page(
                null, // No parent menu, so it won't show up
                __('Gateway Migration', 'convesiopay-woocommerce'),
                __('Gateway → CPay', 'convesiopay-woocommerce'),
                'manage_options',
                'cpay-gateway-migration',
                [__CLASS__, 'admin_page']
            );
        });
        add_action('wp_ajax_cpay_migrate_subscription', [__CLASS__, 'ajax_migrate_subscription']);
        add_action('wp_ajax_cpay_get_migration_status', [__CLASS__, 'ajax_get_migration_status']);
        add_action('wp_ajax_cpay_cancel_migration', [__CLASS__, 'ajax_cancel_migration']);
        add_action('wp_ajax_cpay_bulk_migrate_subscriptions', [__CLASS__, 'ajax_bulk_migrate_subscriptions']);
    }

    /**
     * Admin page for migration
     */
    public static function admin_page() {
        // Only allow logged-in admin users to access this page
        if (!is_user_logged_in() || !current_user_can('administrator')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // Ensure the migration table exists
        self::ensure_table_exists();

        $migration_stats = self::get_migration_stats();
        $pending_subscriptions = self::get_pending_subscriptions();
        
        include dirname(__FILE__) . '/templates/admin-page.php';
    }

    /**
     * Ensure the migration table exists
     */
    private static function ensure_table_exists() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cpay_gateway_migration';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if (!$table_exists) {
            self::create_table();
        }
    }

    /**
     * Get migration statistics
     */
    public static function get_migration_stats() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'cpay_gateway_migration';
        
        $stats = $wpdb->get_results("
            SELECT migration_status, COUNT(*) as count 
            FROM $table_name 
            GROUP BY migration_status
        ");

        $result = [
            'pending' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'failed' => 0,
            'reverted' => 0,
            'total' => 0
        ];

        foreach ($stats as $stat) {
            $result[$stat->migration_status] = (int) $stat->count;
            $result['total'] += (int) $stat->count;
        }

        return $result;
    }

    /**
     * Get all migrations
     */
    public static function get_migrations($search = '') {
        global $wpdb;

        // Force schema update check every time we retrieve migrations
        self::update_table_schema();

        // Get all migrations first
        $migrations = $wpdb->get_results("
            SELECT * FROM {$wpdb->prefix}cpay_gateway_migration 
            ORDER BY created_at DESC
        ");
        
        // If no search term, return all migrations
        if (empty($search)) {
            return $migrations;
        }

        // Filter migrations based on search term using WordPress functions
        $filtered_migrations = [];
        $search_lower = strtolower($search);

        foreach ($migrations as $migration) {
            $subscription = wcs_get_subscription($migration->subscription_id);
            
            if (!$subscription) {
                continue;
            }

            // Check customer email
            $customer_email = strtolower($subscription->get_billing_email());
            if (stripos($customer_email, $search) !== false) {
                $filtered_migrations[] = $migration;
                continue;
            }

            // Check subscription ID
            if (stripos((string)$migration->subscription_id, $search) !== false) {
                $filtered_migrations[] = $migration;
                continue;
            }

            // Check customer name
            $first_name = $subscription->get_billing_first_name();
            $last_name = $subscription->get_billing_last_name();
            $full_name = trim($first_name . ' ' . $last_name);
            
            if (stripos($first_name, $search) !== false || 
                stripos($last_name, $search) !== false || 
                stripos($full_name, $search) !== false) {
                $filtered_migrations[] = $migration;
                continue;
            }
        }

        return $filtered_migrations;
    }

    /**
     * Get pending subscriptions for migration
     */
    public static function get_pending_subscriptions() {
        global $wpdb;

        return $wpdb->get_results("
            SELECT * FROM {$wpdb->prefix}cpay_gateway_migration 
            WHERE migration_status = 'pending'
            ORDER BY created_at ASC
        ");
    }

    /**
     * Scan for subscriptions that can be migrated (generic method)
     */
    public static function scan_subscriptions($source_gateway = null) {
        global $wpdb;

        $source_gateway = $source_gateway ?: self::$default_source_gateway;
        
        // Check if WooCommerce Subscriptions is active
        if (!function_exists('wcs_get_subscriptions')) {
            return new \WP_Error('no_subscriptions', 'WooCommerce Subscriptions is not active');
        }
        
        // Check if source gateway is supported
        if (!isset(self::$supported_source_gateways[$source_gateway])) {
            return new \WP_Error('unsupported_gateway', 'Unsupported source gateway: ' . $source_gateway);
        }

        $gateway_config = self::$supported_source_gateways[$source_gateway];
        
        // Get only active subscriptions and filter manually
        $subscriptions = [];
        $all_subscriptions = wcs_get_subscriptions([
            'subscriptions_per_page' => -1,
            'status' => 'active'
        ]);
        
        // Filter subscriptions manually
        foreach ($all_subscriptions as $subscription) {
            $payment_method = $subscription->get_payment_method();
            
            if (in_array($payment_method, $gateway_config['gateway_ids'])) {
                $subscriptions[] = $subscription;
            }
        }

        $migrated_count = 0;
        $errors = [];

        foreach ($subscriptions as $subscription) {
            // Check if already in migration table
            $existing = $wpdb->get_var($wpdb->prepare("
                SELECT id FROM {$wpdb->prefix}cpay_gateway_migration 
                WHERE subscription_id = %d
            ", $subscription->get_id()));

            if ($existing) {
                // Update existing record with current customer email (in case it changed)
                $customer_email = strtolower($subscription->get_billing_email());
                $convesiopay_customer_id = md5($customer_email);
                
                $wpdb->update(
                    $wpdb->prefix . 'cpay_gateway_migration',
                    [
                        'customer_email' => $customer_email,
                        'convesiopay_customer_id' => $convesiopay_customer_id,
                        'updated_at' => current_time('mysql')
                    ],
                    ['subscription_id' => $subscription->get_id()],
                    ['%s', '%s', '%s'],
                    ['%d']
                );
                continue; // Already queued for migration
            }

            // Validate subscription based on source gateway
            if ($source_gateway === 'authorizenet') {
                if (!self::has_authorizenet_metadata($subscription)) {
                    $error_msg = sprintf('Subscription %d does not have valid Authorize.net metadata', $subscription->get_id());
                    $errors[] = $error_msg;
                    continue;
                }
            }

            // Get customer email for ConvesioPay customer ID mapping
            $customer_email = strtolower($subscription->get_billing_email());
            $convesiopay_customer_id = md5($customer_email);

            // Create migration record
            $result = $wpdb->insert(
                $wpdb->prefix . 'cpay_gateway_migration',
                [
                    'subscription_id' => $subscription->get_id(),
                    'customer_email' => $customer_email,
                    'convesiopay_customer_id' => $convesiopay_customer_id,
                    'migration_status' => self::STATUS_PENDING,
                    'source_gateway' => $source_gateway,
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ],
                ['%d', '%s', '%s', '%s', '%s', '%s', '%s']
            );

            if ($result) {
                $migrated_count++;
            } else {
                $errors[] = sprintf('Failed to create migration record for subscription %d', $subscription->get_id());
            }
        }

        return [
            'migrated_count' => $migrated_count,
            'errors' => $errors
        ];
    }



    /**
     * Check if subscription has valid Authorize.net metadata
     */
    public static function has_authorizenet_metadata($subscription) {
        $authorizenet_meta_patterns = [
            'profile_id' => [
                '_wc_authorize_net_cim_credit_card_customer_profile_id',
                '_wc_authorize_net_credit_card_customer_profile_id',
                '_authorize_net_cim_credit_card_customer_profile_id',
                '_authorize_net_credit_card_customer_profile_id',
                '_wc_authorize_net_cim_credit_card_profile_id',
                '_wc_authorize_net_credit_card_profile_id',
                '_authorize_net_cim_credit_card_profile_id',
                '_authorize_net_credit_card_profile_id'
            ],
            'payment_profile_id' => [
                '_wc_authorize_net_cim_credit_card_payment_profile_id',
                '_wc_authorize_net_credit_card_payment_profile_id',
                '_authorize_net_cim_credit_card_payment_profile_id',
                '_authorize_net_credit_card_payment_profile_id',
                '_wc_authorize_net_cim_credit_card_payment_id',
                '_wc_authorize_net_credit_card_payment_id',
                '_authorize_net_cim_credit_card_payment_id',
                '_authorize_net_credit_card_payment_id'
            ],
            'payment_token' => [
                '_wc_authorize_net_cim_credit_card_payment_token',
                '_wc_authorize_net_credit_card_payment_token',
                '_authorize_net_cim_credit_card_payment_token',
                '_authorize_net_credit_card_payment_token',
                '_wc_authorize_net_cim_credit_card_token',
                '_wc_authorize_net_credit_card_token',
                '_authorize_net_cim_credit_card_token',
                '_authorize_net_credit_card_token'
            ],
            'customer_id' => [
                '_wc_authorize_net_cim_credit_card_customer_id',
                '_wc_authorize_net_credit_card_customer_id',
                '_authorize_net_cim_credit_card_customer_id',
                '_authorize_net_credit_card_customer_id',
                '_wc_authorize_net_cim_credit_card_customer_profile_id',
                '_wc_authorize_net_credit_card_customer_profile_id',
                '_authorize_net_cim_credit_card_customer_profile_id',
                '_authorize_net_credit_card_customer_profile_id'
            ],
            'subscription_id' => [
                '_wc_authorize_net_cim_credit_card_subscription_id',
                '_wc_authorize_net_credit_card_subscription_id',
                '_authorize_net_cim_credit_card_subscription_id',
                '_authorize_net_credit_card_subscription_id',
                '_wc_authorize_net_cim_credit_card_subscription',
                '_wc_authorize_net_credit_card_subscription',
                '_authorize_net_cim_credit_card_subscription',
                '_authorize_net_credit_card_subscription'
            ],
            'transaction_id' => [
                '_wc_authorize_net_cim_credit_card_transaction_id',
                '_wc_authorize_net_credit_card_transaction_id',
                '_authorize_net_cim_credit_card_transaction_id',
                '_authorize_net_credit_card_transaction_id',
                '_wc_authorize_net_cim_credit_card_transaction',
                '_wc_authorize_net_credit_card_transaction',
                '_authorize_net_cim_credit_card_transaction',
                '_authorize_net_credit_card_transaction'
            ]
        ];

        // Check for at least one of the profile ID patterns
        foreach ($authorizenet_meta_patterns['profile_id'] as $meta_key) {
            if ($subscription->get_meta($meta_key)) {
                return true;
            }
        }

        // Check for at least one of the payment profile ID patterns
        foreach ($authorizenet_meta_patterns['payment_profile_id'] as $meta_key) {
            if ($subscription->get_meta($meta_key)) {
                return true;
            }
        }

        // Check for at least one of the payment token patterns
        foreach ($authorizenet_meta_patterns['payment_token'] as $meta_key) {
            if ($subscription->get_meta($meta_key)) {
                return true;
            }
        }

        // Check for at least one of the customer ID patterns
        foreach ($authorizenet_meta_patterns['customer_id'] as $meta_key) {
            if ($subscription->get_meta($meta_key)) {
                return true;
            }
        }

        // Check for at least one of the subscription ID patterns
        foreach ($authorizenet_meta_patterns['subscription_id'] as $meta_key) {
            if ($subscription->get_meta($meta_key)) {
                return true;
            }
        }

        // Check for at least one of the transaction ID patterns
        foreach ($authorizenet_meta_patterns['transaction_id'] as $meta_key) {
            if ($subscription->get_meta($meta_key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get Authorize.net metadata from subscription
     */
    private static function get_authorizenet_metadata($subscription) {
        $metadata = [];
        $authorizenet_meta_patterns = [
            'profile_id' => [
                '_wc_authorize_net_cim_credit_card_customer_profile_id',
                '_wc_authorize_net_credit_card_customer_profile_id',
                '_authorize_net_cim_credit_card_customer_profile_id',
                '_authorize_net_credit_card_customer_profile_id',
                '_wc_authorize_net_cim_credit_card_profile_id',
                '_wc_authorize_net_credit_card_profile_id',
                '_authorize_net_cim_credit_card_profile_id',
                '_authorize_net_credit_card_profile_id'
            ],
            'payment_profile_id' => [
                '_wc_authorize_net_cim_credit_card_payment_profile_id',
                '_wc_authorize_net_credit_card_payment_profile_id',
                '_authorize_net_cim_credit_card_payment_profile_id',
                '_authorize_net_credit_card_payment_profile_id',
                '_wc_authorize_net_cim_credit_card_payment_id',
                '_wc_authorize_net_credit_card_payment_id',
                '_authorize_net_cim_credit_card_payment_id',
                '_authorize_net_credit_card_payment_id'
            ],
            'subscription_id' => [
                '_wc_authorize_net_cim_credit_card_subscription_id',
                '_wc_authorize_net_credit_card_subscription_id',
                '_authorize_net_cim_credit_card_subscription_id',
                '_authorize_net_credit_card_subscription_id',
                '_wc_authorize_net_cim_credit_card_subscription',
                '_wc_authorize_net_credit_card_subscription',
                '_authorize_net_cim_credit_card_subscription',
                '_authorize_net_credit_card_subscription'
            ],
            'transaction_id' => [
                '_wc_authorize_net_cim_credit_card_transaction_id',
                '_wc_authorize_net_credit_card_transaction_id',
                '_authorize_net_cim_credit_card_transaction_id',
                '_authorize_net_credit_card_transaction_id',
                '_wc_authorize_net_cim_credit_card_transaction',
                '_wc_authorize_net_credit_card_transaction',
                '_authorize_net_cim_credit_card_transaction',
                '_authorize_net_credit_card_transaction'
            ],
            'payment_token' => [
                '_wc_authorize_net_cim_credit_card_payment_token',
                '_wc_authorize_net_credit_card_payment_token',
                '_authorize_net_cim_credit_card_payment_token',
                '_authorize_net_credit_card_payment_token',
                '_wc_authorize_net_cim_credit_card_token',
                '_wc_authorize_net_credit_card_token',
                '_authorize_net_cim_credit_card_token',
                '_authorize_net_credit_card_token'
            ],
            'customer_id' => [
                '_wc_authorize_net_cim_credit_card_customer_id',
                '_wc_authorize_net_credit_card_customer_id',
                '_authorize_net_cim_credit_card_customer_id',
                '_authorize_net_credit_card_customer_id',
                '_wc_authorize_net_cim_credit_card_customer_profile_id',
                '_wc_authorize_net_credit_card_customer_profile_id',
                '_authorize_net_cim_credit_card_customer_profile_id',
                '_authorize_net_credit_card_customer_profile_id'
            ]
        ];

        // Get profile ID
        foreach ($authorizenet_meta_patterns['profile_id'] as $meta_key) {
            $value = $subscription->get_meta($meta_key);
            if ($value) {
                $metadata['profile_id'] = $value;
                $metadata['profile_id_meta_key'] = $meta_key;
                break;
            }
        }

        // Get payment profile ID
        foreach ($authorizenet_meta_patterns['payment_profile_id'] as $meta_key) {
            $value = $subscription->get_meta($meta_key);
            if ($value) {
                $metadata['payment_profile_id'] = $value;
                $metadata['payment_profile_id_meta_key'] = $meta_key;
                break;
            }
        }

        // Get subscription ID
        foreach ($authorizenet_meta_patterns['subscription_id'] as $meta_key) {
            $value = $subscription->get_meta($meta_key);
            if ($value) {
                $metadata['subscription_id'] = $value;
                $metadata['subscription_id_meta_key'] = $meta_key;
                break;
            }
        }

        // Get transaction ID
        foreach ($authorizenet_meta_patterns['transaction_id'] as $meta_key) {
            $value = $subscription->get_meta($meta_key);
            if ($value) {
                $metadata['transaction_id'] = $value;
                $metadata['transaction_id_meta_key'] = $meta_key;
                break;
            }
        }

        // Get payment token
        foreach ($authorizenet_meta_patterns['payment_token'] as $meta_key) {
            $value = $subscription->get_meta($meta_key);
            if ($value) {
                $metadata['payment_token'] = $value;
                $metadata['payment_token_meta_key'] = $meta_key;
                break;
            }
        }

        // Get customer ID
        foreach ($authorizenet_meta_patterns['customer_id'] as $meta_key) {
            $value = $subscription->get_meta($meta_key);
            if ($value) {
                $metadata['customer_id'] = $value;
                $metadata['customer_id_meta_key'] = $meta_key;
                break;
            }
        }

        return $metadata;
    }

    /**
     * Get ConvesioPay stored payment methods for a customer
     */
    public static function get_convesiopay_stored_payment_methods($convesiopay_customer_id) {
        $api = new \Woosa\Adyen\Service();
        $test_mode = 'yes' == get_option( 'adn_testmode' );

        $payment_methods_host = $test_mode ? 'api-qa' : 'api';
        $payment_methods_url = 'https://' . $payment_methods_host . '.convesiopay.com/payment/v1/wc-plugin/payment-methods';
        
        $payload = [
            'merchantAccount' => $api->get_merchant(),
            'shopperReference' => $convesiopay_customer_id,
            'channel' => 'Web',
        ];

        $response = \Woosa\Adyen\Request::POST([
            'headers' => $api->headers(),
            'body' => json_encode($payload),
            'authorized' => $api->is_configured()
        ])->send($payment_methods_url);
        
        if ($response->status == 200) {
            $result = \Woosa\Adyen\Util::obj_to_arr($response->body);
            return isset($result['storedPaymentMethods']) ? $result['storedPaymentMethods'] : [];
        }

        return [];
    }

    /**
     * Process migration for a single subscription
     */
    public static function process_migration($migration_id) {
        global $wpdb;

        // Get migration record
        $migration = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}cpay_gateway_migration 
            WHERE id = %d
        ", $migration_id));

        if (!$migration) {
            return new \WP_Error('migration_not_found', 'Migration record not found.');
        }

        // Update status to in progress
        $wpdb->update(
            $wpdb->prefix . 'cpay_gateway_migration',
            ['migration_status' => self::STATUS_IN_PROGRESS, 'updated_at' => current_time('mysql')],
            ['id' => $migration_id]
        );

        try {
            $subscription = wc_get_order($migration->subscription_id);
            if (!$subscription) {
                throw new \Exception('Subscription not found.');
            }

            // Store original subscription data for potential reversion
            $original_data = [
                'payment_method' => $subscription->get_payment_method(),
                'payment_method_title' => $subscription->get_payment_method_title(),
                'recurring_detail_reference' => $subscription->get_meta('_' . 'adn' . '_recurringDetailReference'),
                'shopper_reference' => $subscription->get_meta('_' . 'adn' . '_shopper_reference')
            ];

            // Handle Stripe migrations
            if ($migration->source_gateway === 'stripe') {
                
                // Check if Stripe_Migration class exists
                if (class_exists('\\ConvesioPay\\Stripe_Migration')) {
                    return \ConvesioPay\Stripe_Migration::process_stripe_migration($migration_id);
                } else {
                    throw new \Exception('Stripe migration class not available.');
                }
            }

            // Handle source gateway specific metadata
            if ($migration->source_gateway === 'authorizenet') {
                $authorizenet_metadata = self::get_authorizenet_metadata($subscription);
                $original_data['authorizenet_metadata'] = $authorizenet_metadata;
                
                // Preserve original Authorize.net metadata with cpay prefix for potential reversion
                foreach ($authorizenet_metadata as $key => $value) {
                    if (strpos($key, '_meta_key') === false) { // Skip meta_key entries
                        $subscription->update_meta_data('_cpay_authorizenet_' . $key, $value);
                    }
                }
            }

            // Get ConvesioPay stored payment methods for this customer
            $stored_payment_methods = self::get_convesiopay_stored_payment_methods($migration->convesiopay_customer_id);
            
            if (empty($stored_payment_methods)) {
                throw new \Exception('No stored payment methods found in ConvesioPay for customer: ' . $migration->customer_email);
            }

            // Use the first stored payment method (you might want to add logic to match specific cards)
            $stored_payment_method = $stored_payment_methods[0];
            $stored_payment_method_id = $stored_payment_method['id'];

            // CRITICAL: Use the original ConvesioPay customer ID as the shopper reference
            // This ensures Adyen accepts renewal payments since the storedPaymentMethodId
            // is associated with this specific shopperReference in Adyen's system
            $shopper_reference = $migration->convesiopay_customer_id;

            // Change payment method to ConvesioPay (this determines which gateway handles renewals)
            $subscription->set_payment_method('woosa_adyen_credit_card');
            $subscription->set_payment_method_title('ConvesioPay - Credit Card');
            
            // Add ConvesioPay stored payment method ID (used automatically by gateway during renewal)
            $subscription->update_meta_data('_cpay_recurringDetailReference', $stored_payment_method_id);
            $subscription->update_meta_data('_adn_recurringDetailReference', $stored_payment_method_id);
            
            // Add ConvesioPay shopper reference (used automatically by gateway during renewal)
            $subscription->update_meta_data('_cpay_shopper_reference', $shopper_reference);
            $subscription->update_meta_data('_adn_shopper_reference', $shopper_reference);
            
            $subscription->save();

            // Update pending renewal orders to use ConvesioPay
            if (function_exists('wcs_get_subscriptions_for_order')) {
                $subs = wcs_get_subscriptions_for_order($migration->subscription_id);
                foreach ($subs as $sub) {
                    $renewal_orders = $sub->get_related_orders('all', 'renewal');
                    foreach ($renewal_orders as $order_id) {
                        $order = wc_get_order($order_id);
                        if ($order && $order->has_status('pending')) {
                            $order->set_payment_method('woosa_adyen_credit_card');
                            $order->set_payment_method_title('ConvesioPay - Credit Card');
                            $order->update_meta_data('_cpay_recurringDetailReference', $stored_payment_method_id);
                            $order->update_meta_data('_adn_recurringDetailReference', $stored_payment_method_id);
                            $order->update_meta_data('_cpay_shopper_reference', $shopper_reference);
                            $order->update_meta_data('_adn_shopper_reference', $shopper_reference);
                            $order->save();
                        }
                    }
                }
            }

            // Update migration status with original data for potential reversion
            $wpdb->update(
                $wpdb->prefix . 'cpay_gateway_migration',
                [
                    'migration_status' => self::STATUS_COMPLETED,
                    'convesiopay_payment_method_id' => $stored_payment_method_id,
                    'plugin_shopper_reference' => $shopper_reference,
                    'original_payment_method' => $original_data['payment_method'],
                    'original_payment_method_title' => $original_data['payment_method_title'],
                    'original_recurring_detail_reference' => $original_data['recurring_detail_reference'],
                    'original_shopper_reference' => $original_data['shopper_reference'],
                    'completed_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ],
                ['id' => $migration->id]
            );

            return [
                'success' => true,
                'message' => 'Migration completed successfully.',
                'stored_payment_method_id' => $stored_payment_method_id,
                'plugin_shopper_reference' => $shopper_reference
            ];

        } catch (\Exception $e) {
            // Update status to failed
            $wpdb->update(
                $wpdb->prefix . 'cpay_gateway_migration',
                [
                    'migration_status' => self::STATUS_FAILED,
                    'error_message' => $e->getMessage(),
                    'updated_at' => current_time('mysql')
                ],
                ['id' => $migration_id]
            );

            return new \WP_Error('migration_failed', $e->getMessage());
        }
    }

    /**
     * Revert a migration back to Stripe
     */
    public static function revert_migration($migration_id) {
        global $wpdb;

        // Get migration record
        $migration = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}cpay_gateway_migration 
            WHERE id = %d
        ", $migration_id));

        if (!$migration) {
            return new \WP_Error('migration_not_found', 'Migration record not found.');
        }

        if (!in_array($migration->migration_status, [self::STATUS_COMPLETED, self::STATUS_FAILED])) {
            return new \WP_Error('invalid_status', 'Can only revert completed or failed migrations.');
        }

        try {
            $subscription = wc_get_order($migration->subscription_id);
            if (!$subscription) {
                throw new \Exception('Subscription not found.');
            }

            // Restore original payment method and metadata
            $subscription->set_payment_method($migration->original_payment_method);
            $subscription->set_payment_method_title($migration->original_payment_method_title);
            
            // Handle source gateway specific metadata restoration
            if ($migration->source_gateway === 'authorizenet') {
                // Restore original Authorize.net metadata
                if (!empty($migration->original_recurring_detail_reference)) {
                    $authorizenet_metadata = json_decode($migration->original_recurring_detail_reference, true);
                    if (is_array($authorizenet_metadata)) {
                        foreach ($authorizenet_metadata as $key => $value) {
                            if (strpos($key, '_meta_key') === false) { // Skip meta_key entries
                                $meta_key = $authorizenet_metadata[$key . '_meta_key'] ?? '';
                                if ($meta_key) {
                                    $subscription->update_meta_data($meta_key, $value);
                                }
                            }
                        }
                    }
                }
            } else {
                // Default Stripe/Adyen metadata restoration
                if (!empty($migration->original_recurring_detail_reference)) {
                    $subscription->update_meta_data('_' . 'adn' . '_recurringDetailReference', $migration->original_recurring_detail_reference);
                }
                
                if (!empty($migration->original_shopper_reference)) {
                    $subscription->update_meta_data('_' . 'adn' . '_shopper_reference', $migration->original_shopper_reference);
                }
                
                // Special handling for failed Stripe migrations - restore original Stripe metadata
                if ($migration->source_gateway === 'stripe' && $migration->migration_status === self::STATUS_FAILED) {
                    // Restore original Stripe metadata that was preserved with _cpay_stripe_ prefix
                    $stripe_metadata_keys = [
                        '_cpay_stripe_customer_id' => '_stripe_customer_id',
                        '_cpay_stripe_source_id' => '_stripe_source_id',
                        '_cpay_stripe_payment_method_id' => '_stripe_payment_method_id',
                        '_cpay_stripe_subscription_id' => '_stripe_subscription_id'
                    ];
                    
                    foreach ($stripe_metadata_keys as $cpay_key => $original_key) {
                        $value = $subscription->get_meta($cpay_key);
                        if ($value) {
                            $subscription->update_meta_data($original_key, $value);
                            $subscription->delete_meta_data($cpay_key); // Clean up the cpay copy
                        }
                    }
                    
                    // Restore original payment method if available
                    if (empty($migration->original_payment_method)) {
                        $subscription->set_payment_method('stripe');
                        $subscription->set_payment_method_title('Stripe');
                    }
                }
            }
            
            $subscription->save();

            // Update migration status
            $wpdb->update(
                $wpdb->prefix . 'cpay_gateway_migration',
                [
                    'migration_status' => self::STATUS_REVERTED,
                    'reverted_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ],
                ['id' => $migration_id]
            );

            return [
                'success' => true,
                'message' => 'Migration reverted successfully.',
                'original_payment_method' => $migration->original_payment_method
            ];

        } catch (\Exception $e) {
            return new \WP_Error('revert_failed', $e->getMessage());
        }
    }

    /**
     * Bulk revert migrations
     */
    public static function bulk_revert_migrations($migration_ids) {
        $results = [];
        $success_count = 0;
        $error_count = 0;

        foreach ($migration_ids as $migration_id) {
            $result = self::revert_migration($migration_id);
            
            if (is_wp_error($result)) {
                $results[] = [
                    'migration_id' => $migration_id,
                    'success' => false,
                    'error' => $result->get_error_message()
                ];
                $error_count++;
            } else {
                $results[] = [
                    'migration_id' => $migration_id,
                    'success' => true,
                    'message' => $result['message']
                ];
                $success_count++;
            }
        }

        return [
            'results' => $results,
            'success_count' => $success_count,
            'error_count' => $error_count
        ];
    }

    /**
     * Bulk migrate subscriptions
     */
    public static function bulk_migrate_subscriptions($limit = 10) {
        global $wpdb;

        $pending_migrations = $wpdb->get_results($wpdb->prepare("
            SELECT id FROM {$wpdb->prefix}cpay_gateway_migration 
            WHERE migration_status = 'pending'
            ORDER BY created_at ASC
            LIMIT %d
        ", $limit));

        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($pending_migrations as $migration) {
            $result = self::process_migration($migration->id);
            
            if (is_wp_error($result)) {
                $results['failed']++;
                $results['errors'][] = $result->get_error_message();
            } else {
                $results['success']++;
            }
        }

        return $results;
    }

    /**
     * Bulk migrate migrations
     */
    public static function bulk_migrate($migration_ids) {
        $results = [];
        $success_count = 0;
        $error_count = 0;

        foreach ($migration_ids as $migration_id) {
            $result = self::process_migration($migration_id);

            if (is_wp_error($result)) {
                $results[] = [
                    'migration_id' => $migration_id,
                    'success' => false,
                    'error' => $result->get_error_message()
                ];
                $error_count++;
            } else {
                $results[] = [
                    'migration_id' => $migration_id,
                    'success' => true,
                    'message' => $result['message'] ?? 'Migration completed successfully.'
                ];
                $success_count++;
            }
        }

        return [
            'results' => $results,
            'success_count' => $success_count,
            'error_count' => $error_count
        ];
    }

    /**
     * Get origin domain for shopper reference
     */
    private static function get_origin_domain() {
        return parse_url(home_url(), PHP_URL_HOST);
    }

    /**
     * AJAX handler for migration
     */
    public static function ajax_migrate_subscription() {
        
        check_ajax_referer('cpay_migration_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Insufficient permissions.'));
        }

        $migration_id = intval($_POST['migration_id']);
        $result = self::process_migration($migration_id);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        } else {
            wp_send_json_success($result);
        }
    }

    /**
     * AJAX handler for bulk migration
     */
    public static function ajax_bulk_migrate_subscriptions() {
        check_ajax_referer('cpay_migration_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Insufficient permissions.'));
        }

        // Get the specific migration IDs to process
        $migration_ids = isset($_POST['migration_ids']) ? array_map('intval', $_POST['migration_ids']) : [];
        
        if (empty($migration_ids)) {
            wp_send_json_error('No migration IDs provided.');
        }

        // Process each migration individually
        $results = [];
        $success_count = 0;
        $error_count = 0;

        foreach ($migration_ids as $migration_id) {
            try {
                $result = self::process_migration($migration_id);
                if (is_wp_error($result)) {
                    $results[] = [
                        'migration_id' => $migration_id,
                        'success' => false,
                        'error' => $result->get_error_message()
                    ];
                    $error_count++;
                } else {
                    $results[] = [
                        'migration_id' => $migration_id,
                        'success' => true,
                        'message' => 'Migration completed successfully'
                    ];
                    $success_count++;
                }
            } catch (\Exception $e) {
                $results[] = [
                    'migration_id' => $migration_id,
                    'success' => false,
                    'error' => $e->getMessage()
                ];
                $error_count++;
            }
        }

        wp_send_json_success([
            'results' => $results,
            'success_count' => $success_count,
            'error_count' => $error_count,
            'message' => sprintf('Processed %d migrations: %d successful, %d failed', count($migration_ids), $success_count, $error_count)
        ]);
    }

    /**
     * AJAX handler for migration status
     */
    public static function ajax_get_migration_status() {
        check_ajax_referer('cpay_migration_nonce', 'nonce');

        $stats = self::get_migration_stats();
        wp_send_json_success($stats);
    }

    /**
     * AJAX handler for cancel migration
     */
    public static function ajax_cancel_migration() {
        check_ajax_referer('cpay_migration_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Insufficient permissions.'));
        }

        $migration_id = intval($_POST['migration_id']);
        
        global $wpdb;
        $result = $wpdb->update(
            $wpdb->prefix . 'cpay_gateway_migration',
            [
                'migration_status' => self::STATUS_REVERTED,
                'updated_at' => current_time('mysql')
            ],
            ['id' => $migration_id]
        );

        if ($result) {
            wp_send_json_success(['message' => 'Migration cancelled successfully.']);
        } else {
            wp_send_json_error('Failed to cancel migration.');
        }
    }

    /**
     * Create the migration table
     */
    public static function create_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'cpay_gateway_migration';
        
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            subscription_id bigint(20) unsigned NOT NULL,
            customer_email varchar(255) NOT NULL,
            convesiopay_customer_id varchar(255) NOT NULL,
            migration_status enum('pending','in_progress','completed','failed','reverted') NOT NULL DEFAULT 'pending',
            original_payment_method varchar(255) DEFAULT NULL,
            original_payment_method_title varchar(255) DEFAULT NULL,
            original_recurring_detail_reference text DEFAULT NULL,
            original_shopper_reference varchar(255) DEFAULT NULL,
            convesiopay_payment_method_id varchar(255) DEFAULT NULL,
            plugin_shopper_reference varchar(255) DEFAULT NULL,
            card_match_found tinyint(1) DEFAULT 0 COMMENT 'Whether an exact card match was found during migration',
            stripe_card_last4 varchar(4) DEFAULT NULL COMMENT 'Last 4 digits of the Stripe card',
            convesiopay_card_last4 varchar(4) DEFAULT NULL COMMENT 'Last 4 digits of the selected ConvesioPay card',
            error_message text DEFAULT NULL,
            source_gateway varchar(64) NOT NULL DEFAULT 'stripe',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            completed_at datetime DEFAULT NULL,
            reverted_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY subscription_id (subscription_id),
            KEY migration_status (migration_status),
            KEY customer_email (customer_email)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Ensure new columns exist for existing installations
        self::update_table_schema();
    }
    
    /**
     * Update existing table schema to add new columns
     * This ensures backwards compatibility with existing installations
     */
    public static function update_table_schema() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cpay_gateway_migration';
        
        // Check if new columns exist and add them if they don't
        $columns = $wpdb->get_col("DESCRIBE $table_name", 0);
        
        if (!in_array('card_match_found', $columns)) {
            $result = $wpdb->query("ALTER TABLE $table_name ADD COLUMN card_match_found tinyint(1) DEFAULT 0 COMMENT 'Whether an exact card match was found during migration'");
            if ($result === false) {
                error_log(sprintf('[Migration] ❌ Failed to add card_match_found column: %s', $wpdb->last_error));
            }
        }
        
        if (!in_array('stripe_card_last4', $columns)) {
            $result = $wpdb->query("ALTER TABLE $table_name ADD COLUMN stripe_card_last4 varchar(4) DEFAULT NULL COMMENT 'Last 4 digits of the Stripe card'");
            if ($result === false) {
                error_log(sprintf('[Migration] ❌ Failed to add stripe_card_last4 column: %s', $wpdb->last_error));
            }
        }
        
        if (!in_array('convesiopay_card_last4', $columns)) {
            $result = $wpdb->query("ALTER TABLE $table_name ADD COLUMN convesiopay_card_last4 varchar(4) DEFAULT NULL COMMENT 'Last 4 digits of the selected ConvesioPay card'");
            if ($result === false) {
                error_log(sprintf('[Migration] ❌ Failed to add convesiopay_card_last4 column: %s', $wpdb->last_error));
            }
        }
    }
}