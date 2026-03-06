<?php
/**
 * Module Authorization Hook Settings
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Module_Authorization_Hook_Settings implements Interface_Hook_Settings_Tab{


   /**
    * The id of the tab.
    *
    * @return string
    */
   public static function id(){
      return 'authorization';
   }



   /**
    * The name of the tab.
    *
    * @return string
    */
   public static function name(){
      return __('Authorization', 'convesiopay-woocommerce');
   }



   /**
    * The description of the tab.
    *
    * @return string
    */
   public static function description(){
      return apply_filters(PREFIX . '\module\authorization\settings_tab_description', sprintf(__('Connect %s account', 'convesiopay-woocommerce'), SETTINGS_TAB_NAME));
   }



   /**
    * The icon URL of the tab.
    *
    * @return string
    */
   public static function icon_url(){
      return apply_filters(PREFIX . '\module\authorization\settings_tab_icon', file_get_contents(untrailingslashit(plugin_dir_path(__FILE__)) . '/assets/images/icons/asterisk.svg'));
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
    * Initiates the tab conditionally.
    *
    * @return void
    */
   public static function maybe_init(){

      $initiate = apply_filters(PREFIX . '\authorization\initiate', true);

      if($initiate){

         add_filter(PREFIX . '\module\settings\page\tabs', [__CLASS__, 'add_tab']);
         add_filter(PREFIX . '\module\settings\page\content\fields\\' . self::id(), [__CLASS__, 'add_tab_fields']);

         add_action(PREFIX . '\field_generator\render\\' . PREFIX . '_authorization_ui', [__CLASS__, 'render_authorization_ui'], 99);

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
            'name' => sprintf(
               __('%s Account', 'convesiopay-woocommerce'),
               SETTINGS_TAB_NAME
            ),
            'id'   => PREFIX . '_authorization',
            'type' => 'title',
         ],
         [
            'id'   => PREFIX . '_authorization_ui',
            'type' => PREFIX . '_authorization_ui',
         ],
         [
            'id'   => PREFIX . '_authorization_end',
            'type' => 'sectionend',
         ],
      ], $items);

      return $items;
   }



   /**
    * Renders the output of `authorization_ui` field.
    *
    * @param array $values
    * @return string
    */
   public static function render_authorization_ui($values){
      Module_Authorization::render($values);
   }

}