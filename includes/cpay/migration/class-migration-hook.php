<?php
/**
 * Migration Hook Handler
 *
 * @author ConvesioPay
 */

namespace ConvesioPay;

defined('ABSPATH') || exit;

class Migration_Hook {

    /**
     * Initialize the migration hooks
     */
    public static function init() {
        // Add activation hook to create database table
        add_action('cpay_plugin_activated', [__CLASS__, 'create_migration_table']);
        
        // Add AJAX handlers
        self::init_ajax_handlers();
        
        // Add migration info to subscription details
        if (is_admin()) {
            add_action('woocommerce_admin_subscription_data_after_billing_address', [__CLASS__, 'add_migration_info_to_subscription']);
        }
    }

    /**
     * AJAX handler for scanning subscriptions
     */
    public static function ajax_scan_subscriptions() {
        check_ajax_referer('cpay_migration_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Insufficient permissions.'));
        }

        $source_gateway = sanitize_text_field($_POST['source_gateway'] ?? Gateway_Migration::$default_source_gateway);
        $result = Gateway_Migration::scan_subscriptions($source_gateway);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        } else {
            wp_send_json_success($result);
        }
    }

    /**
     * Create migration database table
     */
    public static function create_migration_table() {
        Gateway_Migration::create_table();
    }

    /**
     * Add migration information to subscription details
     */
    public static function add_migration_info_to_subscription($subscription) {
        global $wpdb;
        
        $migration = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}cpay_gateway_migration 
            WHERE subscription_id = %d
        ", $subscription->get_id()));

        if ($migration) {
            echo '<div class="cpay-migration-info" style="background: #f0f6fc; padding: 10px; margin: 10px 0; border-left: 4px solid #0073aa;">';
            echo '<strong>' . __('Migration Status', 'convesiopay-woocommerce') . '</strong><br>';
            echo sprintf(__('Status: %s', 'convesiopay-woocommerce'), ucfirst(str_replace('_', ' ', $migration->migration_status)));
            
            if ($migration->error_message) {
                echo '<br><strong>' . __('Error:', 'convesiopay-woocommerce') . '</strong> ' . esc_html($migration->error_message);
            }
            
            if ($migration->completed_at) {
                echo '<br><strong>' . __('Completed:', 'convesiopay-woocommerce') . '</strong> ' . date('Y-m-d H:i:s', strtotime($migration->completed_at));
            }
            
            if ($migration->convesiopay_payment_method_id) {
                echo '<br><strong>' . __('ConvesioPay Payment Method ID:', 'convesiopay-woocommerce') . '</strong> ' . esc_html($migration->convesiopay_payment_method_id);
            }
            
            if ($migration->plugin_shopper_reference) {
                echo '<br><strong>' . __('Plugin Shopper Reference:', 'convesiopay-woocommerce') . '</strong> ' . esc_html($migration->plugin_shopper_reference);
            }
            
            echo '</div>';
        }
    }

    /**
     * Add migration hooks to subscription pages
     */
    public static function add_admin_hooks() {
        // Add migration info to subscription details page
        add_action('woocommerce_admin_subscription_data_after_billing_address', [__CLASS__, 'add_migration_info_to_subscription']);
    }

    /**
     * Initialize AJAX handlers
     */
    public static function init_ajax_handlers() {
        add_action('wp_ajax_cpay_scan_subscriptions', [__CLASS__, 'handle_scan_subscriptions']);
        add_action('wp_ajax_cpay_scan_stripe_subscriptions', [__CLASS__, 'handle_scan_stripe_subscriptions']);
        // Note: cpay_scan_authorizenet_subscriptions is handled by AuthorizeNet_Migration class
        add_action('wp_ajax_cpay_migrate_subscription', [__CLASS__, 'handle_migrate_subscription']);
        add_action('wp_ajax_cpay_revert_migration', [__CLASS__, 'handle_revert_migration']);
        add_action('wp_ajax_cpay_bulk_migrate_subscriptions', [__CLASS__, 'handle_bulk_migrate_subscriptions']);
        add_action('wp_ajax_cpay_bulk_revert_migrations', [__CLASS__, 'handle_bulk_revert_migrations']);

    }

    /**
     * Handle scan subscriptions AJAX request (generic)
     */
    public static function handle_scan_subscriptions() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        check_ajax_referer('cpay_migration_nonce', 'nonce');

        $source_gateway = sanitize_text_field($_POST['source_gateway'] ?? Gateway_Migration::$default_source_gateway);
        
        try {
            $result = Gateway_Migration::scan_subscriptions($source_gateway);
            
            if (is_wp_error($result)) {
                wp_send_json_error(['message' => $result->get_error_message()]);
            } else {
                wp_send_json_success($result);
            }
        } catch (\Exception $e) {
            wp_send_json_error(['message' => 'Scan failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Handle scan subscriptions AJAX request
     */
    public static function handle_scan_stripe_subscriptions() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        check_ajax_referer('cpay_migration_nonce', 'nonce');

        $source_gateway = sanitize_text_field($_POST['source_gateway'] ?? Gateway_Migration::$default_source_gateway);
        
        try {
            $result = Gateway_Migration::scan_subscriptions($source_gateway);
            
            if (is_wp_error($result)) {
                wp_send_json_error(['message' => $result->get_error_message()]);
            } else {
                wp_send_json_success($result);
            }
        } catch (\Exception $e) {
            error_log('[Migration] Stripe exception trace: ' . $e->getTraceAsString());
            wp_send_json_error(['message' => 'Stripe scan failed: ' . $e->getMessage()]);
        }
    }


    /**
     * Handle migrate subscription AJAX request
     */
    public static function handle_migrate_subscription() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        check_ajax_referer('cpay_migration_nonce', 'nonce');

        $migration_id = intval($_POST['migration_id'] ?? 0);
        
        if (!$migration_id) {
            wp_send_json_error(['message' => 'Invalid migration ID.']);
        }

        $result = Gateway_Migration::process_migration($migration_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        } else {
            wp_send_json_success($result);
        }
    }

    /**
     * Handle bulk migrate AJAX request
     */
    public static function handle_bulk_migrate() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        check_ajax_referer('cpay_migration_nonce', 'nonce');

        $migration_ids = array_map('intval', $_POST['migration_ids'] ?? []);
        
        if (empty($migration_ids)) {
            wp_send_json_error(['message' => 'No migration IDs provided.']);
        }

        $result = Gateway_Migration::bulk_migrate($migration_ids);
        wp_send_json_success($result);
    }

    /**
     * Handle revert migration AJAX request
     */
    public static function handle_revert_migration() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        check_ajax_referer('cpay_migration_nonce', 'nonce');

        $migration_id = intval($_POST['migration_id'] ?? 0);
        
        if (!$migration_id) {
            wp_send_json_error(['message' => 'Invalid migration ID.']);
        }

        $result = Gateway_Migration::revert_migration($migration_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        } else {
            wp_send_json_success($result);
        }
    }

    /**
     * Handle bulk revert migrations AJAX request
     */
    public static function handle_bulk_revert_migrations() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cpay_migration_nonce')) {
            wp_die('Security check failed');
        }

        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Insufficient permissions');
        }

        $migration_ids = array_map('intval', $_POST['migration_ids'] ?? []);
        
        if (empty($migration_ids)) {
            wp_send_json_error(['message' => 'No migration IDs provided']);
        }

        $result = Gateway_Migration::bulk_revert_migrations($migration_ids);
        
        wp_send_json_success([
            'message' => sprintf(
                'Bulk revert completed. %d successful, %d failed.',
                $result['success_count'],
                $result['error_count']
            ),
            'results' => $result
        ]);
    }

} 