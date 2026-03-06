<?php
/**
 * Module License Hook AJAX
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Module_License_Hook_AJAX implements Interface_Hook{


   /**
    * Initiates the hooks.
    *
    * @return void
    */
   public static function init(){

      add_action('wp_ajax_'.PREFIX.'_license_submission', [__CLASS__, 'process_license_submission']);

   }



   /**
    * Processes AJAX calls.
    *
    * @return string
    */
   public static function process_license_submission(){

      //check to make sure the request is from same server
      if(!check_ajax_referer( 'wsa-nonce', 'security', false )){
         return;
      }

      $key     = sanitize_text_field($_POST['key']);
      $mode    = sanitize_text_field($_POST['mode']);
      $license = new Module_License($key);

      if(empty($key)){
         wp_send_json_error([
            'message' => __('Please provide a license key!', 'convesiopay-woocommerce'),
         ]);
      }

      //save the key
      Option::set('license_key', $key);


      if('activate' === $mode){

         $response = $license->activate();

         if( isset($response->license) ){

            $license->set_active();

            wp_send_json_success();

         }else{

            wp_send_json_error([
               'message' => isset($response->message) ? $response->message : __('This license could have not been activated.', 'convesiopay-woocommerce'),
            ]);
         }

      }elseif('deactivate' === $mode){

         $response = $license->deactivate();

         if( isset($response->license) ){

            $license->set_inactive();

            wp_send_json_success();

         }else{

            $license->set_inactive();
         }

      }elseif('get_update' === $mode){

         $response = $license->get_software_info(true);

         if( isset($response->version) ){

            $message = version_compare( $response->version, VERSION, ">" ) ? sprintf(__('A new update is available, please go to %sPlugins page%s and check.', 'convesiopay-woocommerce'), '<a href="'.admin_url('/plugins.php').'">', '</a>') : __('No updates available, the plugin is already up to date.', 'convesiopay-woocommerce');

            wp_send_json_success([
               'message' => $message,
            ]);

         }else{

            $license->set_inactive();
         }

      }else{
         wp_send_json_error([
            'message' => __('Invalid action provided.', 'convesiopay-woocommerce'),
         ]);
      }

   }

}