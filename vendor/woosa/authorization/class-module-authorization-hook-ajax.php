<?php
/**
 * Module Authorization Hook AJAX
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Module_Authorization_Hook_AJAX implements Interface_Hook{


   /**
    * Initiates the hooks.
    *
    * @return void
    */
   public static function init(){

      add_action('wp_ajax_'.PREFIX.'_process_authorization', [__CLASS__, 'process_authorization']);

   }



   /**
    * Processes the authorization.
    *
    * @return string
    */
   public static function process_authorization(){

      //check to make sure the request is from same server
      if(!check_ajax_referer( 'wsa-nonce', 'security', false )){
         return;
      }

      parse_str(Util::array($_POST)->get('fields'), $fields);

      $action     = Util::array($_POST)->get('args/action');
      $save_extra = apply_filters(PREFIX . '\authorization\save_extra_fields', true);

      if($save_extra){
         foreach($fields as $key => $value){
            if(strpos($key, PREFIX .'_') !== false){
               $value = apply_filters(PREFIX . '\authorization\extra_field_value', $value, $key);
               Option::set($key, $value);
            }
         }
      }

      $ma = new Module_Authorization();
      $ma->set_env($ma->get_env());

      if('authorize' === $action){

         $request = $ma->connect();

      }else{

         $request = $ma->disconnect();
      }

      if($request['success']){

         wp_send_json_success();

      }else{

         wp_send_json_error([
            'message' => Util::array($request)->get('message', 'An error has occurred!')
         ]);
      }
   }

}