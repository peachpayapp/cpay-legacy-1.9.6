<?php
/**
 * Logger Hook
 *
 * @author ConvesioPay - based on the Woosa WC integration
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Logger_Hook implements Interface_Hook{


   /**
    * Initiates the hooks.
    *
    * @return void
    */
   public static function init(){

      add_filter(PREFIX . '\logger\criteria_list', [__CLASS__, 'add_extr_criteria']);
   }



   /**
    * Adds extra criteria in the logger list.
    *
    * @param array $items
    * @return array
    */
   public static function add_extr_criteria($items){

      $ma = new Module_Authorization();

      $items['no_client_key'] = [
         'type'    => 'warning',
         'message' => sprintf(__('The client key for %s is missing, please %sgo to this page%s to generate one.', 'convesiopay-woocommerce'), '<code>'.Service_Util::get_origin_domain().'</code>', '<a href="'.SETTINGS_URL .'&section=tools">','</a>'),
         'hook'    => 'admin_init',
         'active'  => empty(Service_Util::get_origin_key()),
      ];

      return $items;
   }

}