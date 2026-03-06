<?php
/**
 * Module Tools Hook Settings
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Module_Tools_Hook_Settings implements Interface_Hook_Settings_Tab{


   /**
    * The id of the tab.
    *
    * @return string
    */
   public static function id(){
      return 'tools';
   }



   /**
    * The name of the tab.
    *
    * @return string
    */
   public static function name(){
      return __('Tools', 'convesiopay-woocommerce');
   }



   /**
    * The description of the tab.
    *
    * @return string
    */
   public static function description(){
      return __('Clear cache', 'convesiopay-woocommerce');
   }



   /**
    * The icon URL of the tab.
    *
    * @return string
    */
   public static function icon_url(){
      return file_get_contents(untrailingslashit(plugin_dir_path(__FILE__)) . '/assets/images/icons/wrench.svg');
   }



   /**
    * Initiates the hooks.
    *
    * @return void
    */
   public static function init(){

      add_action('admin_init', [__CLASS__, 'maybe_init']);

   }



   /**
    * Initiates the section under a condition.
    *
    * @return void
    */
   public static function maybe_init(){

      $initiate = apply_filters(PREFIX . '\module\tools\initiate', true);

      if($initiate){

         add_filter(PREFIX . '\module\settings\page\tabs', [__CLASS__, 'add_tab'], 98);
         add_filter(PREFIX . '\module\settings\page\content\fields\\' . self::id(), [__CLASS__, 'add_tab_fields']);

         add_action(PREFIX . '\field_generator\render\\' . PREFIX . '_tools_ui', [__CLASS__, 'render_tools_ui']);
      }
   }



   /**
    * Adds the tab in the list.
    *
    * @param array $tabs
    * @return array
    */
   public static function add_tab(array $tabs){

      $tabs[self::id()] = [
         'name'        => self::name(),
         'description' => self::description(),
         'slug'        => self::id(),
         'icon'        => self::icon_url(),
      ];

      return $tabs;
   }



   /**
    * Adds the fields of the tab.
    *
    * @param array $items
    * @return array
    */
   public static function add_tab_fields(array $items){

      $items = array_merge([
         [
            'title' => '',
            'id'    => PREFIX . '_tools',
            'type'  => 'title',
         ],
         [
            'id'   => PREFIX .'_tools_ui',
            'type' => PREFIX .'_tools_ui',
         ],
         [
            'id'   => PREFIX . '_tools_end',
            'type' => 'sectionend',
         ],
      ], $items);

      return $items;
   }



   /**
    * Renders the output of `tools_ui` field.
    *
    * @param array $values
    * @return string
    */
   public static function render_tools_ui($values){

      $ips = array_map(function($id){ return '<code>' . $id . '</code>'; }, Module_Tools::get_ip_whitelist());

      echo Util::get_template('tools-ui.php', [
         'ip_whitelist' => $ips,
         'tools'        => Module_Tools::get_list(),
      ], dirname(dirname(__FILE__)), 'tools/templates');

   }

}