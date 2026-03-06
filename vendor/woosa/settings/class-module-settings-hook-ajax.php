<?php
/**
 * Module Settings Hook AJAX
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Module_Settings_Hook_AJAX implements Interface_Hook{


   /**
    * Initiates the hooks.
    *
    * @return void
    */
   public static function init(){

      add_action('wp_ajax_' . PREFIX . '_save_settings_page', [__CLASS__, 'handle_save_settings_page']);

   }



   /**
    * Processes the request to update save settings.
    *
    * @return string
    */
   public static function handle_save_settings_page(){

      //check to make sure the request is from same server
      if(!check_ajax_referer( 'wsa-nonce', 'security', false )){
         return;
      }

      parse_str(Util::array($_POST)->get('fields'), $fields);

      do_action(PREFIX . '\module\settings\before_save_fields', $fields, $_POST);

      foreach($fields as $name => $value){

         if(Util::prefix('fields') === $name){

            foreach($value as $k => $v){
               Option::set($k, $v);
            }

         }else{
            Option::set($name, $value, false, Util::has_prefix($name));
         }
      }

      do_action(PREFIX . '\module\settings\after_save_fields', $fields, $_POST);


      wp_send_json_success([
         'message' => __('Changes have been saved successfully!', 'convesiopay-woocommerce'),
      ]);
   }

}