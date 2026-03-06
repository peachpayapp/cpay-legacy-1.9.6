<?php
/**
 * Module Tools
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Module_Tools{


   /**
    * The default ip whitelist.
    * @var array
    */
   const DEFAULT_IP_LIST = [
      '104.21.4.191',
   ];



   /**
    * The list of tools.
    *
    * @return array
    */
    public static function get_list(){

      $allow_lrr = Option::get('allow_long_run_requests', 0);
      $list = [
         [
            'id'          => 'allow_long_run_requests',
            'name'        => __('Allow long-running requests', 'convesiopay-woocommerce'),
            'hidden'      => apply_filters(PREFIX . '\module\tools\allow_long_run_requests\hidden', true),
            'description' => sprintf(__('Enable this if the plugin is struggling to process the action list. This adds directives to the %s file to prevent the server from timing out long-running scripts. Specifically, it sets %s and %s environment variables, allowing plugin\'s processes to run without interruption.', 'convesiopay-woocommerce'), '<code>.htaccess</code>', '<code>RewriteRule .* - [E=noabort:1]</code>', '<code>RewriteRule .* - [E=noconntimeout:1]</code>'),
            'warning'     => __('Before enabling, check with your hosting provider to ensure these directives are supported on your server!', 'convesiopay-woocommerce'),
            'btn_class'   => $allow_lrr ? 'button-secondary' : 'button-primary',
            'btn_label'   => $allow_lrr ? __('Click to disable', 'convesiopay-woocommerce') : __('Click to enable', 'convesiopay-woocommerce'),
         ],
         [
            'id'          => 'clear_cache',
            'name'        => __('Clear cache', 'convesiopay-woocommerce'),
            'description' => __('This tool will clear the entire cache of the plugin.', 'convesiopay-woocommerce'),
            'hidden'      => apply_filters(PREFIX . '\module\tools\clear_cache\hidden', false),
         ],
      ];

      $list = apply_filters_deprecated(PREFIX . '\tools\list', [ $list ], '2.1.0', PREFIX . '\module\tools\list');
      $list = apply_filters(PREFIX .'\module\tools\list', $list);

      return array_filter((array) $list);
   }



   /**
    * Retrieves the IP list.
    *
    * @return array
    */
   public static function get_ip_whitelist() {

      $list = self::DEFAULT_IP_LIST;
      $list = apply_filters_deprecated(PREFIX . '\tools\ip-whitelist', [ $list ], '2.1.0', PREFIX . '\module\tools\ip-whitelist');
      $list = apply_filters(PREFIX . '\module\tools\ip-whitelist', $list);

      return array_filter((array) $list);
   }



   /**
    * Removes all transients created by the plugin and flushes WP cache.
    *
    * @return void
    */
   public static function clear_cache(){

      global $wpdb;

      $sql = sprintf("DELETE FROM `$wpdb->options` WHERE `option_name` LIKE ('_transient_%1\$s') OR `option_name` LIKE ('_transient_timeout_%1\$s')", Util::prefix('%'));

      $wpdb->query($sql);

      do_action_deprecated(PREFIX . '\tools\run_clear_cache', [], '2.1.0', PREFIX . '\module\tools\run_clear_cache');

      do_action(PREFIX . '\module\tools\run_clear_cache');

      wp_cache_flush();
   }
}