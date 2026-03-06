<?php
/**
 * Stripe to ConvesioPay Migration Handler
 * 
 * Handles migration of Stripe subscriptions to ConvesioPay
 * with card matching by last4 digits
 */

namespace ConvesioPay;

defined('ABSPATH') || exit;

class Stripe_Migration {
    
    /**
     * Migration status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    // Note: We don't define STATUS_PROCESSING as it doesn't exist in the database enum
    // We use STATUS_IN_PROGRESS instead which is already defined in Gateway_Migration
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_REVERTED = 'reverted';
    
    /**
     * Stripe API configuration
     */
    const STRIPE_API_ENDPOINT = 'https://api.stripe.com/v1/';
    const STRIPE_API_VERSION = '2024-06-20';
    
    /**
     * Stripe metadata patterns for card information
     */
    private static $STRIPE_META_PATTERNS = [
        'customer_id' => [
            '_stripe_customer_id',
            '_stripe_customer',
            'stripe_customer_id',
            'stripe_customer',
            '_stripe_intent_id',
            'stripe_intent_id'
        ],
        'source_id' => [
            '_stripe_source_id',
            '_stripe_source',
            'stripe_source_id',
            'stripe_source',
            '_stripe_token_id',
            'stripe_token_id',
            '_stripe_card_id',
            'stripe_card_id'
        ],
        'payment_method_id' => [
            '_stripe_payment_method_id',
            '_stripe_payment_method',
            'stripe_payment_method_id',
            'stripe_payment_method',
            '_stripe_setup_intent_id',
            'stripe_setup_intent_id'
        ],
        'subscription_id' => [
            '_stripe_subscription_id',
            '_stripe_subscription',
            'stripe_subscription_id',
            'stripe_subscription'
        ]
    ];

    /**
     * Get Stripe API headers (following official plugin pattern)
     */
    private static function get_stripe_headers($secret_key) {
        $headers = [
            'Authorization' => 'Basic ' . base64_encode($secret_key . ':'),
            'Stripe-Version' => self::STRIPE_API_VERSION,
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];
        
        return $headers;
    }
    
    /**
     * Make WordPress HTTP request to Stripe API (production-safe SSL)
     */
    private static function make_stripe_request($api_endpoint, $method = 'GET', $body = null) {
        $credentials = self::get_stripe_api_credentials();
        $secret_key = $credentials['secret_key'];
        
        $url = self::STRIPE_API_ENDPOINT . $api_endpoint;
        $args = [
            'method' => $method,
            'headers' => self::get_stripe_headers($secret_key),
            'timeout' => 70,
        ];
        
        if ($body && $method === 'POST') {
            $args['body'] = $body;
        }
        
        if ($method === 'GET') {
            $response = wp_safe_remote_get($url, $args);
        } else {
            $response = wp_safe_remote_post($url, $args);
        }
        
        if (is_wp_error($response)) {
            error_log("[Stripe Migration] ❌ WordPress HTTP error: " . $response->get_error_message());
            throw new \Exception('WordPress HTTP request failed: ' . $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            error_log("[Stripe Migration] ❌ Stripe API error: {$response_code} - {$response_body}");
            throw new \Exception("Stripe API returned error {$response_code}: {$response_body}");
        }
        
        $decoded_response = json_decode($response_body);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("[Stripe Migration] ❌ JSON decode error: " . json_last_error_msg());
            throw new \Exception('Failed to decode Stripe API response: ' . json_last_error_msg());
        }
        
        return $decoded_response;
    }
    
    /**
     * Check if subscription has valid Stripe metadata
     */
    public static function has_stripe_metadata($subscription) {
        // Check for at least one of the customer ID patterns
        foreach (self::$STRIPE_META_PATTERNS['customer_id'] as $meta_key) {
            if ($subscription->get_meta($meta_key)) {
                return true;
            }
        }

        // Check for at least one of the source ID patterns
        foreach (self::$STRIPE_META_PATTERNS['source_id'] as $meta_key) {
            if ($subscription->get_meta($meta_key)) {
                return true;
            }
        }

        // Check for at least one of the payment method ID patterns
        foreach (self::$STRIPE_META_PATTERNS['payment_method_id'] as $meta_key) {
            if ($subscription->get_meta($meta_key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get Stripe metadata from subscription
     */
    public static function get_stripe_metadata($subscription) {
        $metadata = [];

        // Get customer ID
        foreach (self::$STRIPE_META_PATTERNS['customer_id'] as $meta_key) {
            $value = $subscription->get_meta($meta_key);
            if ($value) {
                $metadata['customer_id'] = $value;
                $metadata['customer_id_meta_key'] = $meta_key;
                break;
            }
        }

        // Get source ID
        foreach (self::$STRIPE_META_PATTERNS['source_id'] as $meta_key) {
            $value = $subscription->get_meta($meta_key);
            if ($value) {
                $metadata['source_id'] = $value;
                $metadata['source_id_meta_key'] = $meta_key;
                break;
            }
        }

        // Get payment method ID
        foreach (self::$STRIPE_META_PATTERNS['payment_method_id'] as $meta_key) {
            $value = $subscription->get_meta($meta_key);
            if ($value) {
                $metadata['payment_method_id'] = $value;
                $metadata['payment_method_id_meta_key'] = $meta_key;
                break;
            }
        }

        // Get subscription ID
        foreach (self::$STRIPE_META_PATTERNS['subscription_id'] as $meta_key) {
            $value = $subscription->get_meta($meta_key);
            if ($value) {
                $metadata['subscription_id'] = $value;
                $metadata['subscription_id_meta_key'] = $meta_key;
                break;
            }
        }

        return $metadata;
    }

    /**
     * Get Stripe API credentials from WooCommerce settings
     */
    private static function get_stripe_api_credentials() {
        $stripe_settings = get_option('woocommerce_stripe_settings', []);
        
        if (empty($stripe_settings)) {
            return false;
        }

        // Check if we're in test mode
        $test_mode = isset($stripe_settings['testmode']) && $stripe_settings['testmode'] === 'yes';
        
        if ($test_mode) {
            $secret_key = isset($stripe_settings['test_secret_key']) ? $stripe_settings['test_secret_key'] : '';
        } else {
            $secret_key = isset($stripe_settings['secret_key']) ? $stripe_settings['secret_key'] : '';
        }

        if (empty($secret_key)) {
            return false;
        }

        return [
            'secret_key' => $secret_key,
            'test_mode' => $test_mode
        ];
    }

    /**
     * Retrieve card last4 digits from Stripe API
     */
    private static function get_stripe_card_last4($stripe_metadata) {
        // Check if we have the necessary metadata
        if (empty($stripe_metadata['payment_method_id']) && empty($stripe_metadata['source_id'])) {
            return false;
        }

        // Get API credentials
        $credentials = self::get_stripe_api_credentials();
        if (!$credentials) {
            error_log('[Stripe Migration] ❌ Could not retrieve Stripe API credentials from WooCommerce settings');
            return false;
        }

        try {
            
            // Configure Stripe API with centralized function
            $stripe_config = self::configure_stripe_api();
            if (!$stripe_config) {
                return false;
            }

            // Determine which ID to use and what type it is
            $identifier = null;
            $id_type = null;
            
            if (!empty($stripe_metadata['payment_method_id'])) {
                $identifier = $stripe_metadata['payment_method_id'];
                $id_type = 'payment_method';
            } elseif (!empty($stripe_metadata['source_id'])) {
                $identifier = $stripe_metadata['source_id'];
                // Check if it's actually a payment method ID (starts with pm_)
                if (strpos($identifier, 'pm_') === 0) {
                    $id_type = 'payment_method';
                } else {
                    $id_type = 'source';
                }
            }
            
            if (!$identifier) {
                return false;
            }
            
            // For payment methods (newer Stripe API)
            if ($id_type === 'payment_method') {
                try {
                    $payment_method = self::make_stripe_request("payment_methods/{$identifier}");
                    
                    if (isset($payment_method->card) && isset($payment_method->card->last4)) {
                        return $payment_method->card->last4;
                    }
                } catch (\Exception $e) {
                    error_log(sprintf('[Stripe Migration] ❌ Error retrieving PaymentMethod: %s', $e->getMessage()));
                    throw $e;
                }
            }
            // For sources (older Stripe API)
            else {
                try {
                    $source = self::make_stripe_request("sources/{$identifier}");
                    
                    if (isset($source->card) && isset($source->card->last4)) {
                        return $source->card->last4;
                    }
                } catch (\Exception $e) {
                    error_log(sprintf('[Stripe Migration] ❌ Error retrieving Source: %s', $e->getMessage()));
                    throw $e;
                }
            }

        } catch (\Exception $e) {
            // Log error but don't fail the migration
            error_log('[Stripe Migration] ❌ Stripe API error: ' . $e->getMessage());
            error_log('[Stripe Migration] ❌ Error trace: ' . $e->getTraceAsString());
            return false;
        }

        return false;
    }

    /**
     * Find the best matching stored payment method in ConvesioPay
     * based on card last4 digits only
     */
    public static function find_matching_stored_payment_method($stored_payment_methods, $stripe_metadata) {
        if (empty($stored_payment_methods)) {
            return null;
        }

        // Try to get card last4 from Stripe API
        $card_last4 = self::get_stripe_card_last4($stripe_metadata);
        
        // Try to find exact match
        foreach ($stored_payment_methods as $method) {
            if (isset($method['lastFour']) && $method['lastFour'] === $card_last4) {
                return $method;
            }
        }
        
        // If no match found, return the first available method
        return $stored_payment_methods[0];
    }

    /**
     * Process Stripe migration for a single subscription
     */
    public static function process_stripe_migration($migration_id) {
        global $wpdb;
        
        // Get migration record
        $migration = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}cpay_gateway_migration 
            WHERE id = %d
        ", $migration_id));
        
        if (!$migration) {
            error_log('[Stripe Migration] ❌ Migration record not found');
            throw new \Exception('Migration record not found');
        }
        
        // Handle different migration statuses
        if ($migration->migration_status === self::STATUS_COMPLETED) {
            error_log('[Stripe Migration] ❌ Migration already completed');
            throw new \Exception('Migration already completed');
        }
        
        if ($migration->migration_status === self::STATUS_FAILED) {
            error_log('[Stripe Migration] 🔄 Retrying failed migration...');
            // Reset to pending for retry
            $wpdb->update(
                $wpdb->prefix . 'cpay_gateway_migration',
                ['migration_status' => self::STATUS_PENDING, 'error_message' => null],
                ['id' => $migration_id],
                ['%s', '%s'],
                ['%d']
            );
            $migration->migration_status = self::STATUS_PENDING;
        }
        
        if ($migration->migration_status === self::STATUS_IN_PROGRESS) {
            error_log('[Stripe Migration] 🔄 Resuming in-progress migration...');
            // Check if this is a fresh attempt or if we should continue
            $last_updated = strtotime($migration->updated_at);
            $time_diff = time() - $last_updated;
            
            // If it's been more than 5 minutes, assume it's safe to retry
            if ($time_diff > 300) {
                error_log('[Stripe Migration] ⏰ Migration has been in progress for more than 5 minutes, resetting to pending...');
                $wpdb->update(
                    $wpdb->prefix . 'cpay_gateway_migration',
                    ['migration_status' => self::STATUS_PENDING, 'error_message' => null],
                    ['id' => $migration_id],
                    ['%s', '%s'],
                    ['%d']
                );
                $migration->migration_status = self::STATUS_PENDING;
            } else {
                error_log('[Stripe Migration] ⚠️ Migration is still in progress (updated ' . $time_diff . ' seconds ago), waiting...');
                throw new \Exception('Migration is still in progress, please wait before retrying');
            }
        }
        
        // Allow pending, in_progress (from Gateway_Migration), and reverted statuses
        if (!in_array($migration->migration_status, [self::STATUS_PENDING, self::STATUS_IN_PROGRESS, self::STATUS_REVERTED])) {
            error_log(sprintf('[Stripe Migration] ❌ Migration has unexpected status: %s', $migration->migration_status));
            throw new \Exception('Migration has unexpected status: ' . $migration->migration_status);
        }
        
        // If status is reverted or in_progress, proceed with migration
        if ($migration->migration_status === self::STATUS_REVERTED) {
            error_log('[Stripe Migration] 🔄 Processing reverted migration...');
        } elseif ($migration->migration_status === self::STATUS_IN_PROGRESS) {
            error_log('[Stripe Migration] 🔄 Processing in-progress migration...');
        }
        
        // Get subscription
        $subscription = wcs_get_subscription($migration->subscription_id);
        if (!$subscription) {
            error_log(sprintf('[Stripe Migration] ❌ Subscription not found: %d', $migration->subscription_id));
            throw new \Exception('Subscription not found: ' . $migration->subscription_id);
        }
        
        try {
            // Status is already set to in_progress by Gateway_Migration, no need to update again
            
            // Get Stripe metadata
            $stripe_metadata = self::get_stripe_metadata($subscription);
            if (empty($stripe_metadata)) {
                error_log('[Stripe Migration] ❌ No valid Stripe metadata found');
                throw new \Exception('No valid Stripe metadata found for subscription: ' . $migration->subscription_id);
            }
            
            // CRITICAL: Call Stripe API to get card details
            try {
                $stripe_card_details = self::get_stripe_card_details($stripe_metadata);
            } catch (\Exception $e) {
                // Don't fail the migration, just log the error and continue
                // We'll fall back to the first available stored payment method
            }
            
            // CRITICAL: Check if we have card details before proceeding
            if (!$stripe_card_details) {
                error_log('[Stripe Migration] 🚨 CRITICAL: No card details from Stripe API - this will prevent payment method matching!');
                error_log('[Stripe Migration] 🚨 CRITICAL: About to throw exception to prevent incomplete migration...');
                throw new \Exception('Failed to retrieve card details from Stripe API - cannot proceed with payment method matching');
            }
            
            // Store original data for potential reversion
            $original_data = [
                'payment_method' => $subscription->get_payment_method(),
                'payment_method_title' => $subscription->get_payment_method_title(),
                'stripe_metadata' => $stripe_metadata,
                'stripe_card_details' => $stripe_card_details ?? null
            ];
            
            // Preserve original Stripe metadata with cpay prefix for potential reversion
            foreach ($stripe_metadata as $key => $value) {
                if (strpos($key, '_meta_key') === false) { // Skip meta_key entries
                    $subscription->update_meta_data('_cpay_stripe_' . $key, $value);
                }
            }
            
            // Get ConvesioPay stored payment methods for this customer
            $stored_payment_methods = Gateway_Migration::get_convesiopay_stored_payment_methods($migration->convesiopay_customer_id);
            
            if (empty($stored_payment_methods)) {
                error_log('[Stripe Migration] ❌ No stored payment methods found in ConvesioPay');
                throw new \Exception('No stored payment methods found in ConvesioPay for customer: ' . $migration->customer_email);
            }
            
            // Find the best matching stored payment method
            $stored_payment_method = self::find_matching_stored_payment_method($stored_payment_methods, $stripe_metadata);
            $stored_payment_method_id = $stored_payment_method['id'];
            
            // Determine if we found an exact card match
            $card_match_found = false;
            $stripe_card_last4 = $stripe_card_details['last4'] ?? null;
            $convesiopay_card_last4 = $stored_payment_method['lastFour'] ?? null;
            
            if ($stripe_card_last4 && $convesiopay_card_last4 && $stripe_card_last4 === $convesiopay_card_last4) {
                $card_match_found = true;
            }
            
            // CRITICAL: Use the original ConvesioPay customer ID as the shopper reference
            $shopper_reference = $migration->convesiopay_customer_id;
            
            // Change payment method to ConvesioPay
            $subscription->set_payment_method('woosa_adyen_credit_card');
            $subscription->set_payment_method_title('ConvesioPay - Credit Card');
            
            // Add ConvesioPay stored payment method ID
            $subscription->update_meta_data('_cpay_recurringDetailReference', $stored_payment_method_id);
            $subscription->update_meta_data('_adn_recurringDetailReference', $stored_payment_method_id);
            
            // Add ConvesioPay shopper reference
            $subscription->update_meta_data('_cpay_shopper_reference', $shopper_reference);
            $subscription->update_meta_data('_adn_shopper_reference', $shopper_reference);
            
            // Add migration metadata
            $subscription->update_meta_data('_cpay_migrated_from', 'stripe');
            $subscription->update_meta_data('_cpay_migration_id', $migration_id);
            $subscription->update_meta_data('_cpay_migration_date', current_time('mysql'));
            
            $subscription->save();
            
            // Update pending renewal orders to use ConvesioPay
            if (function_exists('wcs_get_subscriptions_for_order')) {
                $pending_renewals = wcs_get_subscriptions_for_order($subscription->get_id(), ['status' => 'pending']);
                
                foreach ($pending_renewals as $renewal) {
                    $renewal->set_payment_method('woosa_adyen_credit_card');
                    $renewal->set_payment_method_title('ConvesioPay - Credit Card');
                    $renewal->update_meta_data('_cpay_recurringDetailReference', $stored_payment_method_id);
                    $renewal->update_meta_data('_adn_recurringDetailReference', $stored_payment_method_id);
                    $renewal->update_meta_data('_cpay_shopper_reference', $shopper_reference);
                    $renewal->update_meta_data('_adn_shopper_reference', $shopper_reference);
                    $renewal->save();
                }
            }
            
            // Update migration status to completed
            
            // Build update data - include card matching info only if columns exist
            $update_data = [
                'migration_status' => self::STATUS_COMPLETED,
                'completed_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ];
            
            $update_formats = ['%s', '%s', '%s'];
            
            // Check if new columns exist before trying to update them
            $table_name = $wpdb->prefix . 'cpay_gateway_migration';
            $columns = $wpdb->get_col("DESCRIBE $table_name", 0);
            
            if (in_array('card_match_found', $columns)) {
                $update_data['card_match_found'] = $card_match_found ? 1 : 0;
                $update_formats[] = '%d';
            }
            
            if (in_array('stripe_card_last4', $columns)) {
                $update_data['stripe_card_last4'] = $stripe_card_last4;
                $update_formats[] = '%s';
            }
            
            if (in_array('convesiopay_card_last4', $columns)) {
                $update_data['convesiopay_card_last4'] = $convesiopay_card_last4;
                $update_formats[] = '%s';
            }
            
            $result = $wpdb->update(
                $table_name,
                $update_data,
                ['id' => $migration_id],
                $update_formats,
                ['%d']
            );
            
            // Get card last4 for the return data
            $card_last4 = self::get_stripe_card_last4($stripe_metadata);
            
            return [
                'success' => true,
                'message' => sprintf('Successfully migrated subscription %d from Stripe to ConvesioPay', $migration->subscription_id),
                'stored_payment_method_id' => $stored_payment_method_id,
                'card_last4' => $card_last4 ?: 'unknown'
            ];
            
        } catch (\Exception $e) {
            error_log(sprintf('[Stripe Migration] ❌ MIGRATION FAILED: %s', $e->getMessage()));
            
            // Update migration status to failed
            $wpdb->update(
                $wpdb->prefix . 'cpay_gateway_migration',
                [
                    'migration_status' => self::STATUS_FAILED,
                    'error_message' => $e->getMessage(),
                    'updated_at' => current_time('mysql')
                ],
                ['id' => $migration_id],
                ['%s', '%s', '%s'],
                ['%d']
            );
            
            throw $e;
        }
    }
    
    /**
     * Configure Stripe API with proper key and mode detection
     * 
     * @return array|null Returns array with 'api_key' and 'test_mode' or null on failure
     */
    private static function configure_stripe_api() {
        static $stripe_config = null;
        
        // Return cached config if already set
        if ($stripe_config !== null) {
            return $stripe_config;
        }
        
        try {
            // Get Stripe API key from options with mode detection
            $stripe_settings = get_option('woocommerce_stripe_settings', []);
            if (!$stripe_settings) {
                error_log('[Stripe Migration] ❌ Stripe settings not found in WooCommerce');
                return null;
            }
            
            $secret_key = $stripe_settings['secret_key'] ?? null;
            if (!$secret_key) {
                error_log('[Stripe Migration] ❌ Stripe API key not found in WooCommerce settings');
                return null;
            }
            
            // Determine test mode from WooCommerce Stripe plugin setting
            $test_mode = $stripe_settings['testmode'] ?? 'no';
            
            // Use WordPress HTTP functions like the official Stripe plugin (production-safe SSL)
            
            return [
                'secret_key' => $secret_key,
                'test_mode' => $test_mode === 'yes'
            ];
            
        } catch (\Exception $e) {
            error_log(sprintf('[Stripe Migration] ❌ Failed to configure Stripe API: %s', $e->getMessage()));
            return $stripe_config = false;
        }
    }

    /**
     * Get detailed card information from Stripe API using WordPress HTTP (production-safe SSL)
     * 
     * @param array $stripe_metadata
     * @return array|null
     */
    private static function get_stripe_card_details($stripe_metadata) {
        
        try {
            // Get customer ID directly from the metadata we already retrieved
            $customer_id = $stripe_metadata['customer_id'] ?? null;
            if (!$customer_id) {
                error_log('[Stripe Migration] ❌ No Stripe customer ID found in metadata');
                return null;
            }
            
            // Try to get the payment method using the source_id from metadata
            $source_id = $stripe_metadata['source_id'] ?? null;
            if ($source_id) {
                
                try {
                    // Try to retrieve as payment method first using WordPress HTTP
                    $payment_method = self::make_stripe_request("payment_methods/{$source_id}");
                    if ($payment_method && isset($payment_method->type) && $payment_method->type === 'card') {
                        $card_details = [
                            'last4' => $payment_method->card->last4 ?? null,
                            'brand' => $payment_method->card->brand ?? null,
                            'exp_month' => $payment_method->card->exp_month ?? null,
                            'exp_year' => $payment_method->card->exp_year ?? null,
                            'fingerprint' => $payment_method->card->fingerprint ?? null
                        ];
                        
                        return $card_details;
                    }
                } catch (\Exception $e) {
                    error_log(sprintf('[Stripe Migration] ⚠️ Could not retrieve as payment method, trying as source: %s', $e->getMessage()));
                }
                
                // Try to retrieve as source if payment method failed
                try {
                    $source = self::make_stripe_request("sources/{$source_id}");
                    if ($source && isset($source->type) && $source->type === 'card') {
                        $card_details = [
                            'last4' => $source->card->last4 ?? null,
                            'brand' => $source->card->brand ?? null,
                            'exp_month' => $source->card->exp_month ?? null,
                            'exp_year' => $source->card->exp_year ?? null,
                            'fingerprint' => $source->card->fingerprint ?? null
                        ];
                        
                        return $card_details;
                    }
                } catch (\Exception $e) {
                    error_log(sprintf('[Stripe Migration] ❌ Could not retrieve source: %s', $e->getMessage()));
                }
            }
            
            // Fallback: try to get default payment method from customer
            try {
                $customer = self::make_stripe_request("customers/{$customer_id}");
                if ($customer && isset($customer->default_source) && $customer->default_source) {
                    error_log(sprintf('[Stripe Migration] 💳 Trying default source: %s', $customer->default_source));
                    
                    try {
                        $source = self::make_stripe_request("sources/{$customer->default_source}");
                        if ($source && isset($source->type) && $source->type === 'card') {
                            $card_details = [
                                'last4' => $source->card->last4 ?? null,
                                'brand' => $source->card->brand ?? null,
                                'exp_month' => $source->card->exp_month ?? null,
                                'exp_year' => $source->card->exp_year ?? null,
                                'fingerprint' => $source->card->fingerprint ?? null
                            ];
                            
                            return $card_details;
                        }
                    } catch (\Exception $e) {
                        error_log(sprintf('[Stripe Migration] ❌ Could not retrieve default source: %s', $e->getMessage()));
                    }
                }
            } catch (\Exception $e) {
                error_log(sprintf('[Stripe Migration] ❌ Could not retrieve customer: %s', $e->getMessage()));
            }
            
            return null;
            
        } catch (\Exception $e) {
            error_log(sprintf('[Stripe Migration] ❌ Stripe API error: %s', $e->getMessage()));
            return null;
        }
        
        return null;
    }
}
