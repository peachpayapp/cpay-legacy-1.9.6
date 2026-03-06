<?php
/**
 * Module Settings Hook Dashboard
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Module_Settings_Hook_Dashboard implements Interface_Hook_Settings_Section{


   /**
    * The id of the tab.
    *
    * @return string
    */
   public static function tab_id(){
      return 'dashboard';
   }



   /**
    * Add initialization hooks
    *
    * @return void
    */
   public static function init() {

      add_action('init', [__CLASS__, 'maybe_init']);

   }



   /**
    * Initiates the section under a condition.
    *
    * @return void
    */
   public static function maybe_init(){

      $initiate = apply_filters(PREFIX . '\module\settings\\' . self::tab_id() . '\initiate', true);

      if($initiate){

         add_filter(PREFIX . '\module\settings\page\content\fields\\' . self::tab_id(), [__CLASS__, 'add_section_fields']);
         add_filter(PREFIX . '\module\settings\page\content\fields\\' . self::tab_id(), [__CLASS__, 'add_submit_button'], 99);

      }
   }



   /**
    * Adds the fields of the section.
    *
    * @return array
    */
   public static function add_section_fields($items) {

      $items[] = [
         'name' => __('Settings', 'convesiopay-woocommerce'),
         'id'   => Util::prefix('settings_start'),
         'type' => 'title',
      ];

      $items[] = [
         'name'  => __('Debug mode', 'convesiopay-woocommerce'),
         'id'    => Util::prefix('debug'),
         'type'  => 'toggle',
         'desc'  => __('Enable this if you want to see advanced logs.', 'convesiopay-woocommerce'),
         'value' => Option::get('debug', 'no'),
      ];

      $items[] = [
         'name'  => __('Remove configuration', 'convesiopay-woocommerce'),
         'id'    => Util::prefix('remove_config'),
         'type'  => 'toggle',
         'desc'  => __('Enable this if you want to remove the plugin configuration when is uninstalled.', 'convesiopay-woocommerce'),
         'value' => Option::get('remove_config', 'no'),
      ];

      $items[] = [
         'type' => 'sectionend',
         'id'   => Util::prefix('settings_end'),
      ];


      return $items;

   }



   /**
    * Adds the submit button.
    *
    * @param array $items
    * @return array
    */
   public static function add_submit_button(array $items){

      $items = array_merge($items, [
         [
            'type' => 'title',
            'id'   => PREFIX . '_submit_button',
         ],
         [
            'id'   => PREFIX .'_save_settings',
            'type' => 'submit_button',
         ],
         [
            'type' => 'sectionend',
            'id'   => PREFIX . '_submit_button_end',
         ],
      ]);

      return $items;
   }

}
