<?php
/**
 * Module Settings
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Module_Settings{


   /**
    * Gets the page logo url.
    *
    * @return string
    */
   public static function get_logo_url() {
      return apply_filters(PREFIX . '\module\settings\page\logo_url', untrailingslashit(plugin_dir_url(__FILE__)) . '/assets/images/woosa-logo.png');
   }



   /**
    * Gets the menu icon url.
    *
    * @return string
    */
   public static function get_icon_url() {
      return apply_filters(PREFIX . '\module\settings\menu_icon', '');
   }



   /**
    * Gets the menu position number.
    *
    * @return int
    */
   public static function get_menu_position() {
      return apply_filters(PREFIX . '\module\settings\menu_position', 99);
   }



   /**
    * Gets the page tabs.
    *
    * @return array
    */
    public static function get_tabs() {

      return apply_filters(PREFIX . '\module\settings\page\tabs', [
         'dashboard' => [
            'name'        => __('Dashboard', 'convesiopay-woocommerce'),
            'description' => __('Settings', 'convesiopay-woocommerce'),
            'slug'        => 'dashboard',
            'icon'        => file_get_contents(untrailingslashit(plugin_dir_path(__FILE__)) . '/assets/images/icons/house.svg'),
         ],
      ]);

   }



   /**
    * Gets the current page tab.
    *
    * @return array|mixed
    */
   public static function get_current_tab() {

      $current = $_GET['tab'] ?? 'dashboard';
      $tabs    = self::get_tabs();

      foreach ($tabs as $item) {
         if ($current === $item['slug'] ) {
            return $item;
         }
      }

      return Util::array($tabs)->get('dashboard');

   }



   /**
    * Builds the tab URL.
    *
    * @param string $tab
    * @return string
    */
   public static function get_tab_url($tab = '') {

      return add_query_arg(
         [
            'page' => SETTINGS_TAB_ID,
            'tab'  => $tab['slug']
         ],
         admin_url('admin.php')
      );
   }



   /**
    * Gets the tab icon.
    *
    * @param string $tab
    * @return string
    */
   public static function get_tab_icon($tab){

      if(empty($tab['icon'])){
         return file_get_contents(untrailingslashit(plugin_dir_path(__FILE__)) . '/assets/images/icons/file-lines-regular.svg');
      }

      return $tab['icon'];
   }



   /**
    * Removes all options which have our prefix in case the remove config setting option is enabled.
    *
    * @return void
    */
    public static function clean_settings(){

      global $wpdb;

      $sql    = sprintf("DELETE FROM `{$wpdb->prefix}options` WHERE `option_name` LIKE ('%2\$s') OR `option_name` LIKE ('%s%2\$s')", '%_', Util::prefix('%'));
      $result = $wpdb->query($sql);

      if($result === false){

         Util::log()->error([
            'error' => [
               'message' => $wpdb->last_error,
            ],
            'detail' => [
               'query' => $wpdb->last_query,
            ]
         ], __FILE__, __LINE__);
      }
   }
}