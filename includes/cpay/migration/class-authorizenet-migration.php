<?php
/**
 * Authorize.net to ConvesioPay Subscription Migration
 *
 * @author ConvesioPay
 */

namespace ConvesioPay;

defined('ABSPATH') || exit;

class AuthorizeNet_Migration extends Gateway_Migration {

    /**
     * Authorize.net specific gateway ID patterns
     */
    const AUTHORIZENET_GATEWAY_IDS = [
        'authorize_net_cim_credit_card',
        'authorize_net_credit_card',
        'authorize_net_echeck',
        'wc_authorize_net_cim_credit_card',
        'wc_authorize_net_credit_card',
        'wc_authorize_net_echeck'
    ];

    /**
     * Authorize.net metadata patterns
     */
    const AUTHORIZENET_META_PATTERNS = [
        'profile_id' => [
            '_wc_authorize_net_cim_credit_card_customer_profile_id',
            '_wc_authorize_net_credit_card_customer_profile_id',
            '_authorize_net_cim_credit_card_customer_profile_id',
            '_authorize_net_credit_card_customer_profile_id'
        ],
        'payment_profile_id' => [
            '_wc_authorize_net_cim_credit_card_payment_profile_id',
            '_wc_authorize_net_credit_card_payment_profile_id',
            '_authorize_net_cim_credit_card_payment_profile_id',
            '_authorize_net_credit_card_payment_profile_id'
        ],
        'subscription_id' => [
            '_wc_authorize_net_cim_credit_card_subscription_id',
            '_wc_authorize_net_cim_credit_card_subscription_id',
            '_authorize_net_cim_credit_card_subscription_id',
            '_authorize_net_cim_credit_card_subscription_id'
        ],
        'transaction_id' => [
            '_wc_authorize_net_cim_credit_card_transaction_id',
            '_wc_authorize_net_cim_credit_card_transaction_id',
            '_authorize_net_cim_credit_card_transaction_id',
            '_wc_authorize_net_cim_credit_card_transaction_id'
        ]
    ];

    /**
     * Initialize Authorize.net migration
     */
    public static function init() {
        // Add Authorize.net specific AJAX handlers
        add_action('wp_ajax_cpay_scan_authorizenet_subscriptions', [__CLASS__, 'ajax_scan_authorizenet_subscriptions']);
        add_action('wp_ajax_cpay_migrate_authorizenet_subscription', [__CLASS__, 'ajax_migrate_authorizenet_subscription']);
        
        // Override the default source gateway for Authorize.net scans
        add_filter('cpay_migration_source_gateway', [__CLASS__, 'get_source_gateway'], 10, 1);
    }

    /**
     * Get source gateway for Authorize.net
     */
    public static function get_source_gateway($default_gateway) {
        if (isset($_POST['source_gateway']) && $_POST['source_gateway'] === 'authorizenet') {
            return 'authorizenet';
        }
        return $default_gateway;
    }

    /**
     * Scan for Authorize.net subscriptions that can be migrated
     */
    public static function scan_authorizenet_subscriptions() {
        global $wpdb;

        $migrated_count = 0;
        $errors = [];

        // Get only active subscriptions with Authorize.net payment methods
        $subscriptions = wc_get_orders([
            'type' => 'shop_subscription',
            'payment_method' => self::AUTHORIZENET_GATEWAY_IDS,
            'status' => 'active',
            'limit' => -1
        ]);
        
        // Also try the WCS method for comparison
        if (function_exists('wcs_get_subscriptions')) {
            $all_subscriptions = wcs_get_subscriptions([
                'subscriptions_per_page' => -1,
                'status' => 'active'
            ]);
            
            // Filter manually like the Stripe scan does
            $filtered_subscriptions = [];
            foreach ($all_subscriptions as $subscription) {
                $payment_method = $subscription->get_payment_method();
                
                if (in_array($payment_method, self::AUTHORIZENET_GATEWAY_IDS)) {
                    $filtered_subscriptions[] = $subscription;
                }
            }
            
            // Use the manually filtered results
            $subscriptions = $filtered_subscriptions;
        }

        foreach ($subscriptions as $subscription) {
            // Check if already in migration table
            $existing = $wpdb->get_var($wpdb->prepare("
                SELECT id FROM {$wpdb->prefix}cpay_gateway_migration 
                WHERE subscription_id = %d
            ", $subscription->get_id()));

            if ($existing) {
                continue; // Already queued for migration
            }

            // Validate that this subscription has Authorize.net metadata
            if (!self::has_authorizenet_metadata($subscription)) {
                $errors[] = sprintf('Subscription %d does not have valid Authorize.net metadata', $subscription->get_id());
                continue;
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
                    'migration_status' => 'pending',
                    'source_gateway' => 'authorizenet',
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
     * Get Authorize.net API credentials from WooCommerce settings
     */
    private static function get_authorizenet_api_credentials() {
        error_log('[Authorize.net Migration] 🔍 Searching for Authorize.net API credentials...');
        
        // Try different possible option names for Authorize.net settings
        $possible_options = [
            'woocommerce_authorize_net_cim_credit_card_settings',
            'woocommerce_authorize_net_credit_card_settings',
            'woocommerce_authorize_net_settings'
        ];

        foreach ($possible_options as $option_name) {
            error_log(sprintf('[Authorize.net Migration] 🔍 Checking option: %s', $option_name));
            $settings = get_option($option_name, []);
            
            if (!empty($settings)) {
                error_log(sprintf('[Authorize.net Migration] ✅ Found settings for: %s', $option_name));
                error_log(sprintf('[Authorize.net Migration] 📋 Settings keys: %s', implode(', ', array_keys($settings))));
                
                // Check if we're in test mode - try multiple possible test mode keys
                $test_mode = false;
                if (isset($settings['testmode']) && $settings['testmode'] === 'yes') {
                    $test_mode = true;
                } elseif (isset($settings['environment']) && $settings['environment'] === 'test') {
                    $test_mode = true;
                } elseif (isset($settings['test_mode']) && $settings['test_mode'] === 'yes') {
                    $test_mode = true;
                }
                
                error_log(sprintf('[Authorize.net Migration] 🔍 Test mode: %s', $test_mode ? 'Yes' : 'No'));
                
                if ($test_mode) {
                    $api_login_id = isset($settings['test_api_login_id']) ? $settings['test_api_login_id'] : '';
                    // Try multiple possible test transaction key names
                    $transaction_key = '';
                    if (isset($settings['test_api_transaction_key'])) {
                        $transaction_key = $settings['test_api_transaction_key'];
                    } elseif (isset($settings['test_transaction_key'])) {
                        $transaction_key = $settings['test_transaction_key'];
                    } elseif (isset($settings['test_api_signature_key'])) {
                        $transaction_key = $settings['test_api_signature_key'];
                    }
                    
                    error_log(sprintf('[Authorize.net Migration] 🔍 Test API Login ID: %s', $api_login_id ? 'Found' : 'Missing'));
                    error_log(sprintf('[Authorize.net Migration] 🔍 Test Transaction Key: %s', $transaction_key ? 'Found' : 'Missing'));
                    if ($transaction_key) {
                        error_log(sprintf('[Authorize.net Migration] 🔍 Test Transaction Key source: %s', 
                            isset($settings['test_api_transaction_key']) ? 'test_api_transaction_key' : 
                            (isset($settings['test_transaction_key']) ? 'test_transaction_key' : 'test_api_signature_key')
                        ));
                    }
                } else {
                    $api_login_id = isset($settings['api_login_id']) ? $settings['api_login_id'] : '';
                    // Try multiple possible live transaction key names
                    $transaction_key = '';
                    if (isset($settings['api_transaction_key'])) {
                        $transaction_key = $settings['api_transaction_key'];
                    } elseif (isset($settings['transaction_key'])) {
                        $transaction_key = $settings['transaction_key'];
                    } elseif (isset($settings['api_signature_key'])) {
                        $transaction_key = $settings['api_signature_key'];
                    }
                    
                    error_log(sprintf('[Authorize.net Migration] 🔍 Live API Login ID: %s', $api_login_id ? 'Found' : 'Missing'));
                    error_log(sprintf('[Authorize.net Migration] 🔍 Live Transaction Key: %s', $transaction_key ? 'Found' : 'Missing'));
                    if ($transaction_key) {
                        error_log(sprintf('[Authorize.net Migration] 🔍 Live Transaction Key source: %s', 
                            isset($settings['api_transaction_key']) ? 'api_transaction_key' : 
                            (isset($settings['transaction_key']) ? 'transaction_key' : 'api_signature_key')
                        ));
                    }
                }

                if (!empty($api_login_id) && !empty($transaction_key)) {
                    error_log(sprintf('[Authorize.net Migration] ✅ Found valid credentials for: %s', $option_name));
                    return [
                        'api_login_id' => $api_login_id,
                        'transaction_key' => $transaction_key,
                        'test_mode' => $test_mode
                    ];
                } else {
                    error_log(sprintf('[Authorize.net Migration] ❌ Missing credentials for: %s', $option_name));
                }
            } else {
                error_log(sprintf('[Authorize.net Migration] ❌ No settings found for: %s', $option_name));
            }
        }

        error_log('[Authorize.net Migration] ❌ No valid Authorize.net API credentials found in any settings');
        return false;
    }

    /**
     * Retrieve card last4 digits from Authorize.net API
     */
    private static function get_authorizenet_card_last4($authorizenet_metadata) {
        // Check if we have the necessary metadata
        if (empty($authorizenet_metadata['profile_id']) || empty($authorizenet_metadata['payment_profile_id'])) {
            error_log('[Authorize.net Migration] ❌ No profile_id or payment_profile_id found in metadata');
            return false;
        }

        // Get API credentials
        $credentials = self::get_authorizenet_api_credentials();
        if (!$credentials) {
            error_log('[Authorize.net Migration] ❌ Could not retrieve Authorize.net API credentials from WooCommerce settings');
            return false;
        }

        error_log(sprintf('[Authorize.net Migration] 🔑 Using Authorize.net API (Test Mode: %s)', $credentials['test_mode'] ? 'Yes' : 'No'));

        try {
            // Initialize Authorize.net SDK if available
            if (!class_exists('\AuthorizeNet\Customer')) {
                error_log('[Authorize.net Migration] 🔍 Authorize.net SDK not loaded, attempting to load...');
                
                // Use our custom Authorize.net loader
                if (\ConvesioPay\Vendor\AuthorizeNet\AuthorizeNetLoader::load()) {
                    error_log('[Authorize.net Migration] ✅ Authorize.net SDK loaded successfully via loader');
                } else {
                    error_log('[Authorize.net Migration] ❌ Authorize.net SDK could not be loaded via any method');
                    return false;
                }
            } else {
                error_log('[Authorize.net Migration] ✅ Authorize.net SDK already available');
            }

            // Create Customer object
            $customer = new \AuthorizeNet\Customer($credentials['api_login_id'], $credentials['transaction_key']);
            
            // Get payment profile
            error_log(sprintf('[Authorize.net Migration] �� Making API call to retrieve PaymentProfile: Profile ID %s, Payment Profile ID %s', 
                $authorizenet_metadata['profile_id'], 
                $authorizenet_metadata['payment_profile_id']
            ));
            
            $response = $customer->getPaymentProfile($authorizenet_metadata['profile_id'], $authorizenet_metadata['payment_profile_id']);
            
            if ($response->isOk()) {
                $payment_profile = $response->getPaymentProfile();
                if (isset($payment_profile->payment->creditCard->cardNumber)) {
                    $card_number = $payment_profile->payment->creditCard->cardNumber;
                    $card_last4 = substr($card_number, -4);
                    error_log(sprintf('[Authorize.net Migration] ✅ Successfully retrieved card last4: %s', $card_last4));
                    // Return last 4 digits
                    return $card_last4;
                }
            }

        } catch (\Exception $e) {
            // Log error but don't fail the migration
            error_log('Authorize.net API error retrieving card details: ' . $e->getMessage());
            return false;
        }

        return false;
    }

    /**
     * Find the best matching stored payment method in ConvesioPay
     * based on card last4 digits only
     */
    public static function find_matching_stored_payment_method($stored_payment_methods, $authorizenet_metadata) {
        if (empty($stored_payment_methods)) {
            return null;
        }

        // Try to get card last4 from Authorize.net API
        $card_last4 = self::get_authorizenet_card_last4($authorizenet_metadata);
        
        // Log the card matching process for testing
        $log_message = sprintf(
            '[Authorize.net Migration] Profile ID %s - Card matching process:',
            $authorizenet_metadata['profile_id'] ?? 'unknown'
        );
        
        if ($card_last4) {
            $log_message .= sprintf(' Found card last4: %s', $card_last4);
            
            // Try to find exact match
            foreach ($stored_payment_methods as $method) {
                if (isset($method['lastFour']) && $method['lastFour'] === $card_last4) {
                    $log_message .= sprintf(' ✅ EXACT MATCH FOUND: %s (ID: %s)', $method['lastFour'], $method['id']);
                    error_log($log_message);
                    return $method;
                }
            }
            
            $log_message .= ' ❌ No exact match found in ConvesioPay stored methods';
        } else {
            $log_message .= ' ❌ Could not retrieve card last4 from Authorize.net API';
        }
        
        $log_message .= sprintf(' → Falling back to first available method: %s (ID: %s)', 
            $stored_payment_methods[0]['lastFour'] ?? 'unknown', 
            $stored_payment_methods[0]['id'] ?? 'unknown'
        );
        
        error_log($log_message);
        
        // If no match found, return the first available method
        return $stored_payment_methods[0];
    }

    /**
     * Process Authorize.net migration for a single subscription
     */
    public static function process_authorizenet_migration($migration_id) {
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
            ['migration_status' => 'in_progress', 'updated_at' => current_time('mysql')],
            ['id' => $migration_id]
        );

        try {
            $subscription = wc_get_order($migration->subscription_id);
            if (!$subscription) {
                throw new \Exception('Subscription not found.');
            }

            // Get Authorize.net metadata
            $authorizenet_metadata = self::get_authorizenet_metadata($subscription);

            // Store original subscription data for potential reversion
            $original_data = [
                'payment_method' => $subscription->get_payment_method(),
                'payment_method_title' => $subscription->get_payment_method_title(),
                'authorizenet_metadata' => $authorizenet_metadata
            ];

            // Get ConvesioPay stored payment methods for this customer
            $stored_payment_methods = self::get_convesiopay_stored_payment_methods($migration->convesiopay_customer_id);
            
            if (empty($stored_payment_methods)) {
                throw new \Exception('No stored payment methods found in ConvesioPay for customer: ' . $migration->customer_email);
            }

            // Use card matching logic to find the best stored payment method
            $stored_payment_method = self::find_matching_stored_payment_method($stored_payment_methods, $authorizenet_metadata);
            $stored_payment_method_id = $stored_payment_method['id'];
            
            // Determine if we found an exact card match
            $card_match_found = false;
            $authorizenet_card_last4 = self::get_authorizenet_card_last4($authorizenet_metadata);
            $convesiopay_card_last4 = $stored_payment_method['lastFour'] ?? null;
            
            if ($authorizenet_card_last4 && $convesiopay_card_last4 && $authorizenet_card_last4 === $convesiopay_card_last4) {
                $card_match_found = true;
                error_log('[Authorize.net Migration] 🎯 EXACT CARD MATCH FOUND! Authorize.net: ' . $authorizenet_card_last4 . ' = ConvesioPay: ' . $convesiopay_card_last4);
            } else {
                error_log('[Authorize.net Migration] ⚠️ No exact card match found. Authorize.net: ' . ($authorizenet_card_last4 ?: 'unknown') . ' ≠ ConvesioPay: ' . ($convesiopay_card_last4 ?: 'unknown'));
            }

            // CRITICAL: Use the original ConvesioPay customer ID as the shopper reference
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
            
            // Preserve original Authorize.net metadata with cpay prefix for potential reversion
            foreach ($authorizenet_metadata as $key => $value) {
                if (strpos($key, '_meta_key') === false) { // Skip meta_key entries
                    $subscription->update_meta_data('_cpay_authorizenet_' . $key, $value);
                }
            }
            
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

            // Build update data - include card matching info only if columns exist
            $update_data = [
                'migration_status' => 'completed',
                'convesiopay_payment_method_id' => $stored_payment_method_id,
                'plugin_shopper_reference' => $shopper_reference,
                'original_payment_method' => $original_data['payment_method'],
                'original_payment_method_title' => $original_data['payment_method_title'],
                'original_recurring_detail_reference' => json_encode($original_data['authorizenet_metadata']),
                'original_shopper_reference' => '', // Authorize.net doesn't use shopper reference
                'completed_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ];
            
            // Check if new columns exist before trying to update them
            $table_name = $wpdb->prefix . 'cpay_gateway_migration';
            $columns = $wpdb->get_col("DESCRIBE $table_name", 0);
            
            if (in_array('card_match_found', $columns)) {
                $update_data['card_match_found'] = $card_match_found ? 1 : 0;
            }
            
            if (in_array('stripe_card_last4', $columns)) {
                $update_data['stripe_card_last4'] = $authorizenet_card_last4; // Reusing the column name for consistency
            }
            
            if (in_array('convesiopay_card_last4', $columns)) {
                $update_data['convesiopay_card_last4'] = $convesiopay_card_last4;
            }
            
            // Update migration status with original data for potential reversion
            $wpdb->update(
                $table_name,
                $update_data,
                ['id' => $migration->id]
            );

            // Get card last4 for the return data
            $card_last4 = self::get_authorizenet_card_last4($authorizenet_metadata);

            return [
                'success' => true,
                'message' => sprintf('Successfully migrated subscription %d from Authorize.net to ConvesioPay', $migration->subscription_id),
                'stored_payment_method_id' => $stored_payment_method_id,
                'card_last4' => $card_last4 ?: 'unknown'
            ];

        } catch (\Exception $e) {
            // Update migration status to failed
            $wpdb->update(
                $wpdb->prefix . 'cpay_gateway_migration',
                [
                    'migration_status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'updated_at' => current_time('mysql')
                ],
                ['id' => $migration_id]
            );

            return new \WP_Error('migration_failed', $e->getMessage());
        }
    }

    /**
     * Revert Authorize.net migration for a single subscription
     */
    public static function revert_authorizenet_migration($migration_id) {
        global $wpdb;

        // Get migration record
        $migration = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}cpay_gateway_migration 
            WHERE id = %d
        ", $migration_id));

        if (!$migration) {
            return new \WP_Error('migration_not_found', 'Migration record not found.');
        }

        if (!in_array($migration->migration_status, ['completed'])) {
            return new \WP_Error('invalid_status', 'Can only revert completed migrations.');
        }

        try {
            $subscription = wc_get_order($migration->subscription_id);
            if (!$subscription) {
                throw new \Exception('Subscription not found.');
            }

            // Restore original payment method and metadata
            $subscription->set_payment_method($migration->original_payment_method);
            $subscription->set_payment_method_title($migration->original_payment_method_title);
            
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
            
            $subscription->save();

            // Update migration status
            $wpdb->update(
                $wpdb->prefix . 'cpay_gateway_migration',
                [
                    'migration_status' => 'reverted',
                    'reverted_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ],
                ['id' => $migration_id]
            );

            return [
                'success' => true,
                'message' => 'Authorize.net migration reverted successfully.'
            ];

        } catch (\Exception $e) {
            return new \WP_Error('revert_failed', $e->getMessage());
        }
    }

    /**
     * AJAX handler for scanning Authorize.net subscriptions
     */
    public static function ajax_scan_authorizenet_subscriptions() {
        check_ajax_referer('cpay_migration_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Insufficient permissions.'));
        }

        $result = self::scan_authorizenet_subscriptions();

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        } else {
            wp_send_json_success($result);
        }
    }

    /**
     * AJAX handler for migrating Authorize.net subscription
     */
    public static function ajax_migrate_authorizenet_subscription() {
        check_ajax_referer('cpay_migration_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Insufficient permissions.'));
        }

        $migration_id = intval($_POST['migration_id'] ?? 0);
        
        if (!$migration_id) {
            wp_send_json_error(['message' => 'Invalid migration ID.']);
        }

        $result = self::process_authorizenet_migration($migration_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        } else {
            wp_send_json_success($result);
        }
    }
}