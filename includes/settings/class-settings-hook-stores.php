<?php
/**
 * Settings Hook Stores
 *
 * @author ConvesioPay - based on the Woosa WC integration
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Settings_Hook_Stores implements Interface_Hook_Settings_Tab{


   /**
    * The id of the tab.
    *
    * @return string
    */
   public static function id(){
      return 'stores';
   }



   /**
    * The name of the tab.
    *
    * @return string
    */
   public static function name(){
      return __('Stores', 'convesiopay-woocommerce');
   }



   /**
    * The description of the tab.
    *
    * @return string
    */
   public static function description(){
      return __('Configure stores', 'convesiopay-woocommerce');
   }



   /**
    * The icon URL of the tab.
    *
    * @return string
    */
   public static function icon_url(){
      return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--!Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M547.6 103.8L490.3 13.1C485.2 5 476.1 0 466.4 0H109.6C99.9 0 90.8 5 85.7 13.1L28.3 103.8c-29.6 46.8-3.4 111.9 51.9 119.4c4 .5 8.1 .8 12.1 .8c26.1 0 49.3-11.4 65.2-29c15.9 17.6 39.1 29 65.2 29c26.1 0 49.3-11.4 65.2-29c15.9 17.6 39.1 29 65.2 29c26.2 0 49.3-11.4 65.2-29c16 17.6 39.1 29 65.2 29c4.1 0 8.1-.3 12.1-.8c55.5-7.4 81.8-72.5 52.1-119.4zM499.7 254.9l-.1 0c-5.3 .7-10.7 1.1-16.2 1.1c-12.4 0-24.3-1.9-35.4-5.3V384H128V250.6c-11.2 3.5-23.2 5.4-35.6 5.4c-5.5 0-11-.4-16.3-1.1l-.1 0c-4.1-.6-8.1-1.3-12-2.3V384v64c0 35.3 28.7 64 64 64H448c35.3 0 64-28.7 64-64V384 252.6c-4 1-8 1.8-12.3 2.3z"/></svg>';
   }



   /**
    * Initiates the hooks.
    *
    * @return void
    */
   public static function init(){

      add_filter(PREFIX . '\module\settings\page\tabs', [__CLASS__, 'add_tab'], 30);
      add_filter(PREFIX . '\module\settings\page\content\fields\\' . self::id(), [__CLASS__, 'add_tab_fields']);

      add_action(PREFIX . '\field_generator\render\\' . PREFIX . '_stores_mapping_ui', [__CLASS__, 'render_stores_mapping_ui']);


   }



   /**
    * Initiates the tab conditionally.
    *
    * @return void
    */
   public static function maybe_init(){}



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
            'name' =>  __('Settings', 'convesiopay-woocommerce'),
            'id'   => PREFIX . '_stores_mapping_settings',
            'type' => 'title',
         ],
         [
            'name'     => __('Country source', 'convesiopay-woocommerce'),
            'id'       => PREFIX.'_country_source',
            'type'     => 'select',
            'desc'    => __('Define either the billing or shipping customer country to be used for identifying the corresponding store.', 'convesiopay-woocommerce'),
            'default' => 'billing_country',
            'options' => [
               'billing_country' => __('Billing country', 'convesiopay-woocommerce'),
               'shipping_country' => __('Shipping country', 'convesiopay-woocommerce'),
            ]
         ],
         [
            'id'   => PREFIX .'_save_settings',
            'type' => 'submit_button',
         ],
         [
            'id'   => PREFIX . '_stores_mapping_settings_end',
            'type' => 'sectionend',
         ],
         [
            'name' => __('Store configuration', 'convesiopay-woocommerce'),
            'desc' => __('Define the stores you want to use based on the customer\'s country.', 'convesiopay-woocommerce'),
            'id'   => PREFIX . '_stores_mapping',
            'type' => 'title',
         ],
         [
            'id'   => PREFIX . '_stores_mapping_ui',
            'type' => PREFIX . '_stores_mapping_ui',
         ],
         [
            'id'   => PREFIX . '_stores_mapping_end',
            'type' => 'sectionend',
         ],
      ], $items);

      return $items;

   }



   /**
    * Renders the output of `stores_mapping_ui` field.
    *
    * @param array $values
    * @return void
    */
   public static function render_stores_mapping_ui($values) {

      $stores_mapping = Option::get('stores_mapping', []);

      $limit  = 20;
      $paged  = isset($_GET['term_page']) ? (int) $_GET['term_page'] : 0;
      $offset = $paged > 0 ? $limit * ($paged - 1) : 0;
      $total  = count($stores_mapping);
      $pages  = ceil($total / $limit);

      $results = array_slice($stores_mapping, $offset, $limit);

      echo Util::get_template('stores-mapping-ui.php', [
         'results' => $results,
         'pages' => $pages,
      ], \dirname(__FILE__), 'templates');

   }


}
