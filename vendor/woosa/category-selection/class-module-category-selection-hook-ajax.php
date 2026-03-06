<?php
/**
 * Module Category Selection Hook AJAX
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Module_Category_Selection_Hook_AJAX implements Interface_Hook{


   /**
    * Initiates the hooks.
    *
    * @return void
    */
   public static function init(){

      add_action('wp_ajax_' . PREFIX . '_cs_load_items', [__CLASS__, 'handle_load_items']);

   }



   /**
    * Processes the request to load items.
    *
    * @return string
    */
   public static function handle_load_items(){

      //check to make sure the request is from same server
      if(!check_ajax_referer( 'wsa-nonce', 'security', false )){
         return;
      }

      $item_id = Util::array($_POST)->get('item_id');
      $source  = Util::array($_POST)->get('source');
      $level   = Util::array($_POST)->get('level');

      $mcs   = new Module_Category_Selection($source, $level);
      $items = $mcs->get_subitems($item_id);
      $list  = $mcs->get_list_template($items);
      $trail = $mcs->get_trail_template($item_id);

      wp_send_json_success([
         'list'  => $list,
         'trail' => $trail,
         'last'  => empty($items) ? true : false,
      ]);

   }
}