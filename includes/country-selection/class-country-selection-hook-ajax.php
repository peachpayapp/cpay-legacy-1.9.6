<?php
/**
 * Country Selection Hook AJAX
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Country_Selection_Hook_AJAX implements Interface_Hook{


   /**
    * Initiates the hooks.
    *
    * @return void
    */
   public static function init(){

      add_action('wp_ajax_' . PREFIX . '_load_countries_items', [__CLASS__, 'handle_load_items']);

      add_action('wp_ajax_' . PREFIX . '_sm_connect', [__CLASS__, 'handle_connect']);
      add_action('wp_ajax_' . PREFIX . '_sm_remove', [__CLASS__, 'handle_remove']);

   }



   /**
    * Processes the request to load items.
    *
    * @return void
    */
   public static function handle_load_items(){

      //check to make sure the request is from same server
      if(!check_ajax_referer( 'wsa-nonce', 'security', false )){
         return;
      }

      $item_id = Util::array($_POST)->get('item_id');
      $source  = Util::array($_POST)->get('source');
      $level   = Util::array($_POST)->get('level');

      $mcs   = new Country_Selection($source, $level);
      $items = $mcs->get_subitems($item_id);
      $list  = $mcs->get_list_template($items);
      $trail = $mcs->get_trail_template($item_id);


      wp_send_json_success([
         'list'  => $list,
         'trail' => $trail,
         'last'  => empty($items) ? true : false,
      ]);

   }



   /**
    * Processes the request to connect shops.
    *
    * @return void
    */
   public static function handle_connect(){

      //check to make sure the request is from same server
      if(!check_ajax_referer( 'wsa-nonce', 'security', false )){
         return;
      }

      $url = str_replace('&removed=yes', '', Util::array($_POST)->get('url'));
      $cat = array_filter((array) Util::array($_POST)->get('cat'));

      if(count($cat) != 2){
         wp_send_json_error([
            'message' =>__('Please select the shop and country you want to connect.', 'convesiopay-woocommerce'),
         ]);
      }

      $store_id = $cat[0];//store
      $country  = $cat[1];//country

      $stores_mapping = Option::get('stores_mapping', []);

      if( $store_id === Util::array($stores_mapping)->get($country) ){
         wp_send_json_error([
            'message' =>__('These shop and country are already connected.', 'convesiopay-woocommerce'),
         ]);
      }

      //set new cat id
      $stores_mapping[$country] = $store_id;

      Option::set('stores_mapping', $stores_mapping);

      if(strpos($url, 'connected=yes') === false){
         $url = $url.'&connected=yes';
      }

      wp_send_json_success([
         'redirect_url' => $url,
      ]);

   }



   /**
    * Processes the request to remove connected shops.
    *
    * @return void
    */
   public static function handle_remove(){

      //check to make sure the request is from same server
      if(!check_ajax_referer( 'wsa-nonce', 'security', false )){
         return;
      }

      $url     = str_replace('&connected=yes', '', Util::array($_POST)->get('url'));
      $country  = Util::array($_POST)->get('term_id');

      $stores_mapping = Option::get('stores_mapping', []);

      if (!empty($stores_mapping[$country])) {
         unset($stores_mapping[$country]);

         Option::set('stores_mapping', $stores_mapping);

      }

      if(strpos($url, 'removed=yes') === false){
         $url = $url.'&removed=yes';
      }

      wp_send_json_success([
         'redirect_url' => $url,
      ]);

   }


}
