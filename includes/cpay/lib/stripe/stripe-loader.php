<?php
/**
 * Stripe SDK Loader for ConvesioPay Migration
 * 
 * This file provides a simple way to load the Stripe SDK
 * without requiring external plugin dependencies
 */

namespace ConvesioPay\Vendor\Stripe;

defined('ABSPATH') || exit;

class StripeLoader {
    
    /**
     * Load Stripe SDK from various possible locations
     */
    public static function load() {
        error_log('[Stripe Loader] 🔍 Starting Stripe SDK loading process...');
        
        // Check if Stripe is already loaded
        if (class_exists('\Stripe\StripeClient')) {
            error_log('[Stripe Loader] ✅ Stripe SDK already available');
            return true;
        }
        
        // First, try to load from our local lib directory (highest priority)
        $local_lib_path = dirname(__DIR__) . '/lib/stripe/init.php';
        if (file_exists($local_lib_path)) {
            error_log('[Stripe Loader] 📁 Found local Stripe SDK at: ' . $local_lib_path);
            try {
                require_once $local_lib_path;
                error_log('[Stripe Loader] ✅ Local Stripe SDK loaded successfully');
                
                // Verify it loaded correctly
                if (class_exists('\Stripe\StripeClient')) {
                    error_log('[Stripe Loader] ✅ Stripe SDK classes verified');
                    return true;
                } else {
                    error_log('[Stripe Loader] ❌ Local SDK loaded but classes not found');
                }
            } catch (\Exception $e) {
                error_log('[Stripe Loader] ❌ Error loading local SDK: ' . $e->getMessage());
            }
        } else {
            error_log('[Stripe Loader] ❌ Local Stripe SDK not found at: ' . $local_lib_path);
        }
        
        // Second, try to load from WooCommerce Stripe plugin if available
        if (class_exists('WC_Stripe')) {
            error_log('[Stripe Loader] 🔍 WooCommerce Stripe plugin detected, checking for SDK...');
            // The WooCommerce Stripe plugin should have already loaded the SDK
            if (class_exists('\Stripe\StripeClient')) {
                error_log('[Stripe Loader] ✅ Stripe SDK available via WooCommerce Stripe plugin');
                return true;
            }
        }
        
        // Third, try to load from any other available autoloader
        $possible_paths = [
            WP_CONTENT_DIR . '/plugins/woocommerce-gateway-stripe/vendor/autoload.php',
            WP_CONTENT_DIR . '/plugins/woocommerce-gateway-stripe/lib/init.php',
            WP_CONTENT_DIR . '/plugins/stripe-woocommerce/vendor/autoload.php',
            WP_CONTENT_DIR . '/plugins/stripe-woocommerce/lib/init.php',
        ];
        
        foreach ($possible_paths as $path) {
            if (file_exists($path)) {
                error_log('[Stripe Loader] 🔍 Found potential Stripe SDK at: ' . $path);
                try {
                    require_once $path;
                    if (class_exists('\Stripe\StripeClient')) {
                        error_log('[Stripe Loader] ✅ Stripe SDK loaded from: ' . $path);
                        return true;
                    }
                } catch (\Exception $e) {
                    error_log('[Stripe Loader] ❌ Error loading from ' . $path . ': ' . $e->getMessage());
                }
            }
        }
        
        error_log('[Stripe Loader] ❌ Stripe SDK could not be loaded from any source');
        return false;
    }
    
    /**
     * Get the Stripe client instance
     */
    public static function getClient($api_key = null) {
        if (!self::load()) {
            throw new \Exception('Stripe SDK could not be loaded');
        }
        
        if ($api_key) {
            \Stripe\Stripe::setApiKey($api_key);
        }
        
        return new \Stripe\StripeClient($api_key);
    }
    
    /**
     * Check if Stripe SDK is available
     */
    public static function isAvailable() {
        return class_exists('\Stripe\StripeClient');
    }
}

