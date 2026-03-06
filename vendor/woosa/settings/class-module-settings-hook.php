<?php
/**
 * Module Settings Hook
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Module_Settings_Hook implements Interface_Hook{


   /**
    * Initiates the hooks.
    *
    * @return void
    */
   public static function init() {

      add_action('init', [__CLASS__, 'maybe_init']);

      add_action(PREFIX . '\core\state\uninstalled', [__CLASS__, 'clean_settings']);

   }



   /**
    * Initiates the module under a condition.
    *
    * @return void
    */
   public static function maybe_init() {

      $initiate = apply_filters(PREFIX . '\module\settings\initiate', true);

      if($initiate){

         add_action('admin_menu', [__CLASS__, 'add_admin_page']);

         add_action(PREFIX . '\module\settings\page\top', [__CLASS__, 'render_page_top'], 10);
         add_action(PREFIX . '\module\settings\page\content', [__CLASS__, 'render_page_content'], 10);
         add_action(PREFIX . '\module\settings\page\bottom', [__CLASS__, 'render_page_bottom'], 10);

         add_action('in_admin_header', [__CLASS__, 'hide_wp_admin_notices'], 99);

         add_filter(PREFIX . '\core\plugin_action_links', [__CLASS__, 'add_action_link']);

      }

   }



   /**
    * Adds the page into WP admin menu.
    *
    * @return void
    */
   public static function add_admin_page() {

      add_menu_page(
         SETTINGS_TAB_NAME,
         SETTINGS_TAB_NAME,
         'manage_options',
         SETTINGS_TAB_ID,
         [__CLASS__, 'render_page'],
         Module_Settings::get_icon_url(),
         Module_Settings::get_menu_position()
      );

   }



   /**
    * Renders the settings page.
    *
    * @return string
    */
   public static function render_page() {

      echo Util::get_template('settings-page.php', [
         'logo_url'    => Module_Settings::get_logo_url(),
         'tabs'        => Module_Settings::get_tabs(),
         'current_tab' => Module_Settings::get_current_tab(),
      ], dirname(dirname(__FILE__)), 'settings/templates');

   }



   /**
    * Renders the top section of the page.
    *
    * @param $tab
    * @return string|void
    */
   public static function render_page_top($tab) {

      $fields = apply_filters(PREFIX . '\module\settings\page\top\fields\\' . Util::array($tab)->get('slug'), []);

      if (!empty($fields)) {

         $mfg = new Module_Field_Generator;
         $mfg->set_fields($fields, 'settings_page_top_' . Util::array($tab)->get('slug'));
         $mfg->render();

      }

   }



   /**
    * Renders the content section of the page.
    *
    * @param $tab
    * @return string|void
    */
   public static function render_page_content($tab) {

      $fields = apply_filters(PREFIX . '\module\settings\page\content\fields\\' . Util::array($tab)->get('slug'), []);

      if (!empty($fields)) {

         $mfg = new Module_Field_Generator;
         $mfg->set_fields($fields, 'settings_page_content_' . Util::array($tab)->get('slug'));
         $mfg->render();

      }

   }



   /**
    * Renders the bottom section of the page.
    *
    * @param $tab
    * @return string|void
    */
   public static function render_page_bottom($tab) {

      $fields = apply_filters(PREFIX . '\module\settings\page\bottom\fields\\' . Util::array($tab)->get('slug'), []);

      if (!empty($fields)) {

         $mfg = new Module_Field_Generator;
         $mfg->set_fields($fields, 'settings_page_bottom_' . Util::array($tab)->get('slug'));
         $mfg->render();

      }

   }



   /**
    * Hides all admin notices.
    *
    * @return void
    */
   public static function hide_wp_admin_notices(){

      if (SETTINGS_TAB_ID === Util::array($_GET)->get('page')) {
         remove_all_actions( 'user_admin_notices' );
         remove_all_actions( 'admin_notices' );
      }
   }



   /**
    * Adds action link to the settings page.
    *
    * @param array $links
    * @return array
    */
   public static function add_action_link($links){

      $links['actions'] = array_merge([
         SETTINGS_URL => __('Settings', 'convesiopay-woocommerce')
      ], $links['actions']);

      return $links;
   }



   /**
    * In case the `remove config` setting is enabled then clean options table.
    *
    * @return void
    */
    public static function clean_settings(){

      if('yes' === Option::get('remove_config')){
         Module_Settings::clean_settings();
      }
   }
}
