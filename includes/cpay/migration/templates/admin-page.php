<?php
/**
 * Stripe to ConvesioPay Migration Admin Page
 */

defined('ABSPATH') || exit;

$search_term = isset($_GET['migration_search']) ? sanitize_text_field($_GET['migration_search']) : '';
$migrations = \ConvesioPay\Gateway_Migration::get_migrations($search_term);
$stats = \ConvesioPay\Gateway_Migration::get_migration_stats();

// Pagination logic
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 50; // Standard page size for good balance of usability and performance
$total_migrations = count($migrations);
$total_pages = ceil($total_migrations / $per_page);
$start = ($paged - 1) * $per_page;
$end = min($start + $per_page, $total_migrations);
$migrations_page = array_slice($migrations, $start, $per_page);
?>

<div class="wrap">
    <h1><?php _e('Subscription Payment Method Migration', 'convesiopay-woocommerce'); ?></h1>
    
    <div class="notice notice-info">
        <p><?php _e('This tool allows you to migrate existing subscriptions from another payment provider to ConvesioPay without requiring customer interaction.', 'convesiopay-woocommerce'); ?></p>
        <p><strong><?php _e('Note:', 'convesiopay-woocommerce'); ?></strong> <?php _e('This migration preserves all original data. No original subscriptions are cancelled or modified.', 'convesiopay-woocommerce'); ?></p>
    </div>

    <!-- Source Gateway Selector -->
    <div style="margin: 20px 0;">
        <label for="source_gateway"><strong><?php _e('Source Gateway:', 'convesiopay-woocommerce'); ?></strong></label>
        <select id="source_gateway" name="source_gateway">
            <option value="stripe" <?php selected(isset($_GET['source_gateway']) ? $_GET['source_gateway'] : 'stripe', 'stripe'); ?>>Stripe</option>
            <option value="authorizenet" <?php selected(isset($_GET['source_gateway']) ? $_GET['source_gateway'] : 'stripe', 'authorizenet'); ?>>Authorize.net</option>
        </select>
        <span class="description"><?php _e('Select the payment gateway to migrate from', 'convesiopay-woocommerce'); ?></span>
    </div>



    <!-- Migration Statistics -->
    <div class="migration-stats">
        <h2><?php _e('Migration Statistics', 'convesiopay-woocommerce'); ?></h2>
        <div class="stats-grid">
            <div class="stat-item">
                <span class="stat-number"><?php echo $stats['pending']; ?></span>
                <span class="stat-label"><?php _e('Pending', 'convesiopay-woocommerce'); ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo $stats['completed']; ?></span>
                <span class="stat-label"><?php _e('Completed', 'convesiopay-woocommerce'); ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo $stats['failed']; ?></span>
                <span class="stat-label"><?php _e('Failed', 'convesiopay-woocommerce'); ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo $stats['reverted']; ?></span>
                <span class="stat-label"><?php _e('Reverted', 'convesiopay-woocommerce'); ?></span>
            </div>
        </div>
    </div>

    <!-- Action Buttons and Search -->
    <div class="migration-actions">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <button type="button" id="scan-subscriptions" class="button button-primary">
                    <?php _e('Scan Subscriptions', 'convesiopay-woocommerce'); ?>
                </button>
                
                <?php if ($stats['pending'] > 0 || $stats['reverted'] > 0): ?>
                    <button type="button" id="bulk-migrate" class="button button-secondary" disabled data-count="0">
                        <?php printf(__('Bulk Migrate (%d)', 'convesiopay-woocommerce'), 0); ?>
                    </button>
                <?php endif; ?>
                
                <?php if ($stats['completed'] > 0): ?>
                    <button type="button" id="bulk-revert" class="button button-danger" disabled data-count="0">
                        <?php printf(__('Bulk Revert (%d)', 'convesiopay-woocommerce'), 0); ?>
                    </button>
                <?php endif; ?>
            </div>
            
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="position: relative;">
                    <input type="text" id="migration-search" name="migration_search" placeholder="<?php _e('Search by name, email, or subscription ID', 'convesiopay-woocommerce'); ?>" style="width: 350px;" value="<?php echo esc_attr(isset($_GET['migration_search']) ? $_GET['migration_search'] : ''); ?>">
                    <span id="search-spinner" style="float: none; margin: 0; position: absolute; right: 8px; top: 50%; transform: translateY(-50%); display: none; width: 16px; height: 16px; border: 2px solid #f3f3f3; border-top: 2px solid #0073aa; border-radius: 50%; animation: spin 1s linear infinite;"></span>
                </div>
                <?php if (!empty($search_term)): ?>
                    <button type="button" id="clear-search" class="button button-secondary"><?php _e('Clear', 'convesiopay-woocommerce'); ?></button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Migration Table -->
    <?php if (!empty($migrations_page)): ?>
        <div class="migration-table-container">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th class="check-column">
                            <input type="checkbox" id="select-all-migrations">
                        </th>
                        <th><?php _e('Subscription ID', 'convesiopay-woocommerce'); ?></th>
                        <th><?php _e('Customer Name', 'convesiopay-woocommerce'); ?></th>
                        <th><?php _e('Customer Email', 'convesiopay-woocommerce'); ?></th>
                        <th><?php _e('Source Gateway', 'convesiopay-woocommerce'); ?></th>
                        <th><?php _e('Status', 'convesiopay-woocommerce'); ?></th>
                        <th style="text-align: center;"><?php _e('Card Match', 'convesiopay-woocommerce'); ?></th>
                        <th><?php _e('Scan Date', 'convesiopay-woocommerce'); ?></th>
                        <th><?php _e('Actions', 'convesiopay-woocommerce'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($migrations_page as $migration): ?>
                        <tr data-migration-id="<?php echo esc_attr($migration->id); ?>">
                            <td class="check-column">
                                <input type="checkbox" class="migration-checkbox" value="<?php echo esc_attr($migration->id); ?>">
                            </td>
                            <td>
                                <a href="<?php echo admin_url('post.php?post=' . $migration->subscription_id . '&action=edit'); ?>" target="_blank">
                                    #<?php echo esc_html($migration->subscription_id); ?>
                                </a>
                            </td>
                            <td>
                                <?php 
                                $customer_name = '';
                                $subscription = wc_get_order($migration->subscription_id);
                                if ($subscription) {
                                    $first_name = $subscription->get_billing_first_name();
                                    $last_name = $subscription->get_billing_last_name();
                                    $customer_name = trim($first_name . ' ' . $last_name);
                                }
                                echo esc_html($customer_name ?: '—');
                                ?>
                            </td>
                            <td><?php echo esc_html($migration->customer_email); ?></td>
                            <td>
                                <?php 
                                $source_gateway_name = 'Unknown';
                                if (isset($migration->source_gateway)) {
                                    switch ($migration->source_gateway) {
                                        case 'stripe':
                                            $source_gateway_name = 'Stripe';
                                            break;
                                        case 'authorizenet':
                                            $source_gateway_name = 'Authorize.net';
                                            break;
                                        default:
                                            $source_gateway_name = ucfirst($migration->source_gateway);
                                    }
                                }
                                echo esc_html($source_gateway_name);
                                ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo esc_attr($migration->migration_status); ?>">
                                    <?php echo esc_html(ucfirst(str_replace('_', ' ', $migration->migration_status))); ?>
                                </span>
                            </td>
                                    <td style="text-align: center;">
            <?php if ($migration->migration_status === 'completed' && isset($migration->card_match_found)): ?>
                <?php if ($migration->card_match_found): ?>
                    <span class="card-match-icon card-match-found" title="<?php echo esc_attr(sprintf(__('Exact card match found! Stripe: %s = ConvesioPay: %s', 'convesiopay-woocommerce'), $migration->stripe_card_last4 ?: 'unknown', $migration->convesiopay_card_last4 ?: 'unknown')); ?>">
                        <span class="dashicons dashicons-yes-alt"></span>
                    </span>
                <?php else: ?>
                    <span class="card-match-icon card-match-fallback" title="<?php echo esc_attr(sprintf(__('No exact match. Stripe: %s ≠ ConvesioPay: %s (used fallback)', 'convesiopay-woocommerce'), $migration->stripe_card_last4 ?: 'unknown', $migration->convesiopay_card_last4 ?: 'unknown')); ?>">
                        <span class="dashicons dashicons-randomize"></span>
                    </span>
                <?php endif; ?>
            <?php else: ?>
                <span class="card-match-icon card-match-pending" title="<?php echo esc_attr(__('Migration pending - card matching will be determined during migration', 'convesiopay-woocommerce')); ?>">
                    <span class="dashicons dashicons-clock"></span>
                </span>
            <?php endif; ?>
        </td>
                            <td><?php echo esc_html(date('Y-m-d H:i:s', strtotime($migration->created_at))); ?></td>
                            <td class="actions">
                                <?php if ($migration->migration_status === 'pending' || $migration->migration_status === 'reverted'): ?>
                                    <button type="button" class="button button-small migrate-single" data-migration-id="<?php echo esc_attr($migration->id); ?>">
                                        <?php _e('Migrate', 'convesiopay-woocommerce'); ?>
                                    </button>
                                <?php elseif ($migration->migration_status === 'in_progress'): ?>
                                    <button type="button" class="button button-small migrate-single" data-migration-id="<?php echo esc_attr($migration->id); ?>">
                                        <?php _e('Retry', 'convesiopay-woocommerce'); ?>
                                    </button>
                                    <button type="button" class="button button-small button-secondary revert-single" data-migration-id="<?php echo esc_attr($migration->id); ?>">
                                        <?php _e('Revert', 'convesiopay-woocommerce'); ?>
                                    </button>
                                <?php elseif ($migration->migration_status === 'completed'): ?>
                                    <button type="button" class="button button-small revert-single" data-migration-id="<?php echo esc_attr($migration->id); ?>">
                                        <?php _e('Revert', 'convesiopay-woocommerce'); ?>
                                    </button>
                                <?php elseif ($migration->migration_status === 'failed'): ?>
                                    <button type="button" class="button button-small migrate-single" data-migration-id="<?php echo esc_attr($migration->id); ?>">
                                        <?php _e('Retry', 'convesiopay-woocommerce'); ?>
                                    </button>
                                    <button type="button" class="button button-small button-secondary revert-single" data-migration-id="<?php echo esc_attr($migration->id); ?>">
                                        <?php _e('Revert', 'convesiopay-woocommerce'); ?>
                                    </button>
                                <?php endif; ?>
                                
                                <?php if (!empty($migration->error_message) && $migration->migration_status === 'failed'): ?>
                                    <div class="error-message">
                                        <strong><?php _e('Error:', 'convesiopay-woocommerce'); ?></strong>
                                        <?php echo esc_html($migration->error_message); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($total_pages > 1): ?>
        <div class="tablenav-pages" style="margin: 20px 0; text-align: right;">
            <span class="displaying-num"><?php printf(__('%d items', 'convesiopay-woocommerce'), $total_migrations); ?></span>
            
            <?php
            // Smart pagination logic
            $current_page = $paged;
            $total_pages = $total_pages;
            $range = 2; // Number of pages to show on each side of current page
            
            // Previous button
            if ($current_page > 1): ?>
                <a class="first-page button" href="<?php echo esc_url(add_query_arg('paged', 1)); ?>">
                    <span class="screen-reader-text"><?php _e('First page', 'convesiopay-woocommerce'); ?></span>
                    <span aria-hidden="true">«</span>
                </a>
                <a class="prev-page button" href="<?php echo esc_url(add_query_arg('paged', $current_page - 1)); ?>">
                    <span class="screen-reader-text"><?php _e('Previous page', 'convesiopay-woocommerce'); ?></span>
                    <span aria-hidden="true">‹</span>
                </a>
            <?php else: ?>
                <span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>
                <span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>
            <?php endif; ?>
            
            <span class="paging-input">
                <label for="current-page-selector" class="screen-reader-text"><?php _e('Current page', 'convesiopay-woocommerce'); ?></label>
                <input class="current-page" id="current-page-selector" type="text" name="paged" value="<?php echo $current_page; ?>" size="3" aria-describedby="table-paging">
                <span class="tablenav-paging-text"> of <span class="total-pages"><?php echo $total_pages; ?></span></span>
            </span>
            
            <?php
            // Page numbers with smart ellipsis
            $start_page = max(1, $current_page - $range);
            $end_page = min($total_pages, $current_page + $range);
            
            // Show first page if not in range
            if ($start_page > 1): ?>
                <a class="page-numbers" href="<?php echo esc_url(add_query_arg('paged', 1)); ?>">1</a>
                <?php if ($start_page > 2): ?>
                    <span class="page-numbers dots">…</span>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php
            // Show page numbers in range
            for ($i = $start_page; $i <= $end_page; $i++): ?>
                <?php if ($i == $current_page): ?>
                    <span class="page-numbers current" aria-current="page"><?php echo $i; ?></span>
                <?php else: ?>
                    <a class="page-numbers" href="<?php echo esc_url(add_query_arg('paged', $i)); ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php
            // Show last page if not in range
            if ($end_page < $total_pages): ?>
                <?php if ($end_page < $total_pages - 1): ?>
                    <span class="page-numbers dots">…</span>
                <?php endif; ?>
                <a class="page-numbers" href="<?php echo esc_url(add_query_arg('paged', $total_pages)); ?>"><?php echo $total_pages; ?></a>
            <?php endif; ?>
            
            <?php
            // Next button
            if ($current_page < $total_pages): ?>
                <a class="next-page button" href="<?php echo esc_url(add_query_arg('paged', $current_page + 1)); ?>">
                    <span class="screen-reader-text"><?php _e('Next page', 'convesiopay-woocommerce'); ?></span>
                    <span aria-hidden="true">›</span>
                </a>
                <a class="last-page button" href="<?php echo esc_url(add_query_arg('paged', $total_pages)); ?>">
                    <span class="screen-reader-text"><?php _e('Last page', 'convesiopay-woocommerce'); ?></span>
                    <span aria-hidden="true">»</span>
                </a>
            <?php else: ?>
                <span class="tablenav-pages-navspan button disabled" aria-hidden="true">›</span>
                <span class="tablenav-pages-navspan button disabled" aria-hidden="true">»</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="no-migrations">
            <p><?php _e('No migrations found. Click "Scan Subscriptions" to begin.', 'convesiopay-woocommerce'); ?></p>
        </div>
    <?php endif; ?>
</div>

<style>
@keyframes spin {
    0% { transform: translateY(-50%) rotate(0deg); }
    100% { transform: translateY(-50%) rotate(360deg); }
}

.migration-stats {
    margin: 20px 0;
    padding: 20px;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
    margin-top: 15px;
}

.stat-item {
    text-align: center;
    padding: 15px;
    background: #f9f9f9;
    border-radius: 4px;
}

.stat-number {
    display: block;
    font-size: 24px;
    font-weight: bold;
    color: #0073aa;
}

.stat-label {
    display: block;
    margin-top: 5px;
    color: #666;
}

.migration-actions {
    margin: 20px 0;
    padding: 15px;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
}

.migration-actions .button {
    margin-right: 10px;
}

.migration-table-container {
    margin-top: 20px;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
}

.status-pending { background: #fff3cd; color: #856404; }
.status-in_progress { background: #cce5ff; color: #004085; }

/* Improved pagination styles */
.tablenav-pages {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    margin: 20px 0;
}

.tablenav-pages .displaying-num {
    margin-right: 15px;
    color: #666;
}

.tablenav-pages .paging-input {
    display: flex;
    align-items: center;
    gap: 5px;
    margin: 0 15px;
}

.tablenav-pages .current-page {
    width: 40px;
    text-align: center;
    padding: 2px 4px;
    border: 1px solid #ddd;
    border-radius: 3px;
}

.tablenav-pages .page-numbers {
    display: inline-block;
    padding: 4px 8px;
    margin: 0 2px;
    text-decoration: none;
    color: #0073aa;
    border: 1px solid #ddd;
    border-radius: 3px;
    min-width: 20px;
    text-align: center;
}

.tablenav-pages .page-numbers:hover {
    background: #f0f0f1;
    color: #135e96;
}

.tablenav-pages .page-numbers.current {
    background: #0073aa;
    color: white;
    border-color: #0073aa;
}

.tablenav-pages .page-numbers.dots {
    border: none;
    color: #666;
    cursor: default;
}

.tablenav-pages .button {
    padding: 4px 8px;
    margin: 0 2px;
    min-width: 20px;
    text-align: center;
}

.tablenav-pages .button.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
.status-completed { background: #d4edda; color: #155724; }
.status-failed { background: #f8d7da; color: #721c24; }
.status-reverted { background: #e2e3e5; color: #383d41; }

        .card-match-icon {
            font-size: 16px;
            width: 20px;
            height: 20px;
            display: inline-block;
            text-align: center;
            cursor: help;
        }

        .card-match-found .dashicons {
            color: #46b450;
        }

        .card-match-fallback .dashicons {
            color: #ffb900;
        }

        .card-match-pending .dashicons {
            color: #72aee6;
        }



        .card-match-icon .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
        }

.actions .button {
    margin-right: 5px;
    margin-bottom: 5px;
}

.error-message {
    margin-top: 5px;
    padding: 5px;
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    border-radius: 3px;
    font-size: 12px;
    color: #721c24;
}

.no-migrations {
    text-align: center;
    padding: 40px;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    color: #666;
}

.migration-table-container table.wp-list-table th,
.migration-table-container table.wp-list-table td {
    padding: 8px 12px;
    vertical-align: middle;
}
.migration-table-container table.wp-list-table th.check-column,
.migration-table-container table.wp-list-table td.check-column {
    width: 36px;
    min-width: 36px;
    max-width: 36px;
    text-align: center;
    vertical-align: middle;
    padding-left: 0;
    padding-right: 0;
}
.migration-table-container table.wp-list-table input[type="checkbox"] {
    margin: 0 auto;
    display: block;
    position: relative;
    top: 1px;
}
</style>

<script>
jQuery(document).ready(function($) {
    const nonce = '<?php echo wp_create_nonce('cpay_migration_nonce'); ?>';
    
    // Search functionality
    $('#migration-search').on('input', function() {
        const searchTerm = $(this).val();
        const currentUrl = new URL(window.location);
        
        if (searchTerm) {
            currentUrl.searchParams.set('migration_search', searchTerm);
        } else {
            currentUrl.searchParams.delete('migration_search');
        }
        
        // Preserve source gateway selection
        const sourceGateway = $('#source_gateway').val();
        currentUrl.searchParams.set('source_gateway', sourceGateway);
        
        // Show loading spinner
        $('#search-spinner').show();
        
        // Add a small delay to avoid too many requests while typing
        clearTimeout(window.searchTimeout);
        window.searchTimeout = setTimeout(function() {
            window.location.href = currentUrl.toString();
        }, 500);
    });

    // Source Gateway change handler
    $('#source_gateway').on('change', function() {
        const sourceGateway = $(this).val();
        const currentUrl = new URL(window.location);
        
        currentUrl.searchParams.set('source_gateway', sourceGateway);
        
        // Preserve search term if present
        const searchTerm = $('#migration-search').val();
        if (searchTerm) {
            currentUrl.searchParams.set('migration_search', searchTerm);
        }
        
        window.location.href = currentUrl.toString();
    });

    // Clear Search button
    $('#clear-search').on('click', function() {
        const currentUrl = new URL(window.location);
        currentUrl.searchParams.delete('migration_search');
        
        // Preserve source gateway selection
        const sourceGateway = $('#source_gateway').val();
        currentUrl.searchParams.set('source_gateway', sourceGateway);
        
        window.location.href = currentUrl.toString();
    });

    // Scan Subscriptions
    $('#scan-subscriptions').on('click', function() {
        const button = $(this);
        const sourceGateway = $('#source_gateway').val();
        button.prop('disabled', true).text('<?php _e('Scanning...', 'convesiopay-woocommerce'); ?>');
        
        // Determine the correct AJAX action based on source gateway
        let ajaxAction = 'cpay_scan_stripe_subscriptions'; // default
        if (sourceGateway === 'authorizenet') {
            ajaxAction = 'cpay_scan_authorizenet_subscriptions';
        }
        
        $.post(ajaxurl, {
            action: ajaxAction,
            source_gateway: sourceGateway,
            nonce: nonce
        })
        .done(function(response) {
            if (response.success) {
                // Preserve the selected gateway when reloading
                const currentUrl = new URL(window.location);
                currentUrl.searchParams.set('source_gateway', sourceGateway);
                // Preserve search term if present
                const searchTerm = $('#migration-search').val();
                if (searchTerm) {
                    currentUrl.searchParams.set('migration_search', searchTerm);
                }
                window.location.href = currentUrl.toString();
            } else {
                // Handle both error response formats: string or object with message key
                let errorMessage = 'Unknown error occurred';
                if (typeof response.data === 'string') {
                    errorMessage = response.data;
                } else if (response.data && response.data.message) {
                    errorMessage = response.data.message;
                } else if (response.data) {
                    errorMessage = response.data;
                }
                alert('Error: ' + errorMessage);
            }
        })
        .fail(function() {
            alert('<?php _e('An error occurred while scanning subscriptions.', 'convesiopay-woocommerce'); ?>');
        })
        .always(function() {
            button.prop('disabled', false).text('<?php _e('Scan Subscriptions', 'convesiopay-woocommerce'); ?>');
        });
    });

    // Individual Migration
    $('.migrate-single').on('click', function() {
        const button = $(this);
        const migrationId = button.data('migration-id');
        
        if (!confirm('<?php _e('Are you sure you want to migrate this subscription?', 'convesiopay-woocommerce'); ?>')) {
            return;
        }
        
        button.prop('disabled', true).text('<?php _e('Migrating...', 'convesiopay-woocommerce'); ?>');
        
        $.post(ajaxurl, {
            action: 'cpay_migrate_subscription',
            migration_id: migrationId,
            nonce: nonce
        })
        .done(function(response) {
            if (response.success) {
                location.reload();
            } else {
                // Handle both error response formats: string or object with message key
                let errorMessage = 'Unknown error occurred';
                if (typeof response.data === 'string') {
                    errorMessage = response.data;
                } else if (response.data && response.data.message) {
                    errorMessage = response.data.message;
                } else if (response.data) {
                    errorMessage = response.data;
                }
                alert('Error: ' + errorMessage);
            }
        })
        .fail(function() {
            alert('<?php _e('An error occurred during migration.', 'convesiopay-woocommerce'); ?>');
        })
        .always(function() {
            button.prop('disabled', false).text('<?php _e('Migrate', 'convesiopay-woocommerce'); ?>');
        });
    });
    
    // Individual Revert
    $('.revert-single').on('click', function() {
        const button = $(this);
        const migrationId = button.data('migration-id');
        
        if (!confirm('<?php _e('Are you sure you want to revert this migration? This will restore the original payment method.', 'convesiopay-woocommerce'); ?>')) {
            return;
        }
        
        button.prop('disabled', true).text('<?php _e('Reverting...', 'convesiopay-woocommerce'); ?>');
        
        $.post(ajaxurl, {
            action: 'cpay_revert_migration',
            migration_id: migrationId,
            nonce: nonce
        })
        .done(function(response) {
            if (response.success) {
                location.reload();
            } else {
                // Handle both error response formats: string or object with message key
                let errorMessage = 'Unknown error occurred';
                if (typeof response.data === 'string') {
                    errorMessage = response.data;
                } else if (response.data && response.data.message) {
                    errorMessage = response.data.message;
                } else if (response.data) {
                    errorMessage = response.data;
                }
                alert('Error: ' + errorMessage);
            }
        })
        .fail(function() {
            alert('<?php _e('An error occurred while reverting migration.', 'convesiopay-woocommerce'); ?>');
        })
        .always(function() {
            button.prop('disabled', false).text('<?php _e('Revert', 'convesiopay-woocommerce'); ?>');
        });
    });
    
    // Bulk Operations
    $('#select-all-migrations').on('change', function() {
        $('.migration-checkbox').prop('checked', $(this).is(':checked'));
    });
    
    function getSelectedMigrationIds() {
        return $('.migration-checkbox:checked').map(function() {
            return $(this).val();
        }).get();
    }
    
    function updateBulkMigrateButton() {
        const selected = getSelectedMigrationIds();
        const button = $('#bulk-migrate');
        button.prop('disabled', selected.length === 0);
        button.text('<?php _e('Bulk Migrate', 'convesiopay-woocommerce'); ?>' + ' (' + selected.length + ')');
        button.data('count', selected.length);
    }

    function updateBulkRevertButton() {
        const selected = getSelectedMigrationIds();
        const button = $('#bulk-revert');
        button.prop('disabled', selected.length === 0);
        button.text('<?php _e('Bulk Revert', 'convesiopay-woocommerce'); ?>' + ' (' + selected.length + ')');
        button.data('count', selected.length);
    }

    $('.migration-checkbox, #select-all-migrations').on('change', function() {
        updateBulkMigrateButton();
        updateBulkRevertButton();
    });
    // Call once on page load to set initial state
    updateBulkMigrateButton();
    updateBulkRevertButton();
    
    // Page input functionality
    $('#current-page-selector').on('keypress', function(e) {
        if (e.which === 13) { // Enter key
            const page = parseInt($(this).val());
            const totalPages = parseInt($('.total-pages').text());
            
            if (page >= 1 && page <= totalPages) {
                const url = new URL(window.location);
                url.searchParams.set('paged', page);
                window.location.href = url.toString();
            } else {
                alert('<?php _e('Please enter a valid page number.', 'convesiopay-woocommerce'); ?>');
                $(this).val('<?php echo $paged; ?>');
            }
        }
    });
    
    // Bulk Migrate
    $('#bulk-migrate').on('click', function() {
        const migrationIds = getSelectedMigrationIds();
        if (migrationIds.length === 0) {
            alert('<?php _e('Please select migrations to process.', 'convesiopay-woocommerce'); ?>');
            return;
        }
        
        if (!confirm('<?php _e('Are you sure you want to migrate the selected subscriptions?', 'convesiopay-woocommerce'); ?>')) {
            return;
        }
        
        const button = $(this);
        button.prop('disabled', true).text('<?php _e('Migrating...', 'convesiopay-woocommerce'); ?>');
        
        $.post(ajaxurl, {
            action: 'cpay_bulk_migrate_subscriptions',
            migration_ids: migrationIds,
            nonce: nonce
        })
        .done(function(response) {
            if (response.success) {
                location.reload();
            } else {
                // Handle both error response formats: string or object with message key
                let errorMessage = 'Unknown error occurred';
                if (typeof response.data === 'string') {
                    errorMessage = response.data;
                } else if (response.data && response.data.message) {
                    errorMessage = response.data.message;
                } else if (response.data) {
                    errorMessage = response.data;
                }
                alert('Error: ' + errorMessage);
            }
        })
        .fail(function() {
            alert('<?php _e('An error occurred during bulk migration.', 'convesiopay-woocommerce'); ?>');
        })
        .always(function() {
            button.prop('disabled', false).text(button.data('original-text') || '<?php _e('Bulk Migrate', 'convesiopay-woocommerce'); ?>');
        });
    });
    
    // Bulk Revert
    $('#bulk-revert').on('click', function() {
        const migrationIds = getSelectedMigrationIds();
        if (migrationIds.length === 0) {
            alert('<?php _e('Please select migrations to process.', 'convesiopay-woocommerce'); ?>');
            return;
        }
        
        if (!confirm('<?php _e('Are you sure you want to revert the selected migrations? This will restore the original payment methods.', 'convesiopay-woocommerce'); ?>')) {
            return;
        }
        
        const button = $(this);
        button.prop('disabled', true).text('<?php _e('Reverting...', 'convesiopay-woocommerce'); ?>');
        
        $.post(ajaxurl, {
            action: 'cpay_bulk_revert_migrations',
            migration_ids: migrationIds,
            nonce: nonce
        })
        .done(function(response) {
            if (response.success) {
                location.reload();
            } else {
                // Handle both error response formats: string or object with message key
                let errorMessage = 'Unknown error occurred';
                if (typeof response.data === 'string') {
                    errorMessage = response.data;
                } else if (response.data && response.data.message) {
                    errorMessage = response.data.message;
                } else if (response.data) {
                    errorMessage = response.data;
                }
                alert('Error: ' + errorMessage);
            }
        })
        .fail(function() {
            alert('<?php _e('An error occurred during bulk revert.', 'convesiopay-woocommerce'); ?>');
        })
        .always(function() {
            button.prop('disabled', false).text(button.data('original-text') || '<?php _e('Bulk Revert', 'convesiopay-woocommerce'); ?>');
        });
    });
    

});
</script> 