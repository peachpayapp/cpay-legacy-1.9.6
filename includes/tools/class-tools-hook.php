<?php
/**
 * Tools Hook
 *
 * @author ConvesioPay - based on the Woosa WC integration
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Tools_Hook implements Interface_Hook{


   /**
    * Initiates the hooks.
    *
    * @return void
    */
   public static function init(){

      add_filter(PREFIX . '\module\tools\list', [__CLASS__, 'define_tools']);
      add_filter(PREFIX . '\module\tools\run_tool', [__CLASS__, 'run_tools']);

   }



   /**
    * Defines the tools.
    *
    * @param array $list
    * @return array
    */
   public static function define_tools($list){

      $list = array_merge($list, [
         [
            'id'          => 'generate_client_key',
            'name'        => __('Generate client key', 'convesiopay-woocommerce'),
            'description' => __('This will generate a client key for the current domain.', 'convesiopay-woocommerce')
         ],
      ]);

      return $list;
   }



   /**
    * Runs the tools.
    *
    * @param string $id
    * @return void
    */
   public static function run_tools($id){

      switch($id){

         case 'generate_client_key':

            Service_Util::generate_origin_keys();

            break;
      }
   }


}