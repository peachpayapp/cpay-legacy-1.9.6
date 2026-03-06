<?php
/**
 * Module License Hook Settings
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Module_License_Hook_Settings implements Interface_Hook_Settings_Section{


   /**
    * The id of the tab.
    *
    * @return string
    */
   public static function tab_id(){
      return 'dashboard';
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

      $initiate = apply_filters(PREFIX . '\license\initiate', true);

      if($initiate){
         add_filter(PREFIX . '\module\settings\page\content\fields\\' . self::tab_id(), [__CLASS__, 'add_section_fields'], 9);

         add_action(PREFIX . '\field_generator\render\\' . PREFIX . '_license_ui', [__CLASS__, 'render_license_ui'], 9, 2);
      }

   }



   /**
    * Adds the fields of the section.
    *
    * @param array $items
    * @return array
    */
   public static function add_section_fields(array $items){

      $items = array_merge([
         [
            'name' => __('My Account', 'convesiopay-woocommerce'),
            'id'   => Util::prefix('license'),
            'type' => 'title',
         ],
         [
            'name' => __( 'License key', 'convesiopay-woocommerce' ),
            'desc' => sprintf(
               __( 'Fill in your Woosa license key below, to be able to receive plugin updates, product updates and order updates.
                  Do you need more licenses? You can easily %srequest an upgrade of your subscription%s.', 'convesiopay-woocommerce' ),
               '<a href="https://convesiopay.com/upgrade" target="_blank">',
               '</a>'
            ),
            'id'   => Util::prefix('license_ui'),
            'type' => Util::prefix('license_ui'),
         ],
         [
            'id'   => Util::prefix('license_end'),
            'type' => 'sectionend',
         ],
      ], $items);

      return $items;
   }



   /**
    * Renders the output of `_license_ui` field.
    *
    * @param array $values
    * @return string
    */
   public static function render_license_ui($values){
      Module_License::render($values);
   }

}
