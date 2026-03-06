<?php
/**
 * My Account Hook AJAX
 *
 * @author ConvesioPay - based on the Woosa WC integration
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class My_Account_Hook_AJAX implements Interface_Hook{


   /**
    * Initiates the hooks.
    *
    * @return void
    */
   public static function init(){

      add_action('wp_ajax_'.PREFIX.'_remove_card', [__CLASS__, 'handle_remove_card']);

      add_action('wp_ajax_'.PREFIX.'_remove_gdpr', [__CLASS__, 'handle_remove_personal_data']);
   }



   /**
    * Processes the request to remove the card.
    *
    * @since 1.0.10 - update cached payment methods
    * @since 1.0.3
    * @return string
    */
   public static function handle_remove_card(){

      //check to make sure the request is from same server
      if(!check_ajax_referer( 'wsa-nonce', 'security', false )){
         return;
      }

      $recurr_reference  = Util::array($_POST)->get('reference');
      $shopper_reference = Service_Util::get_shopper_reference();

      Service::recurring()->disable( $shopper_reference, $recurr_reference );

      //update cache
      Core::update_cached_payment_methods();

      wp_send_json_success();

   }



   /**
    * Processes the request to remove the personal data.
    *
    * @since 1.1.0
    * @return string
    */
   public static function handle_remove_personal_data(){

      //check to make sure the request is from same server
      if(!check_ajax_referer( 'wsa-nonce', 'security', false )){
         return;
      }

      $order_id = Util::array($_POST)->get('order_id');
      $order = wc_get_order($order_id);

      if($order instanceof \WC_Order){

         $reference = $order->get_meta('_' . PREFIX . '_payment_pspReference');
         $api       = new Service;
         $response  = $api->remove_personal_data($reference);

         if($response->status == 200){

            $order->update_meta_data('_' . PREFIX . '_gdpr_removed', 'yes');
            $order->add_order_note(__('The customer has removed the personal and payment data attached to this order payment in Adyen.', 'convesiopay-woocommerce'));
            $order->save();

            wp_send_json_success();

         }else{

            $message = isset($response->body->message) ? $response->body->message : __('Something went wrong, please try again later or contact us.', 'convesiopay-woocommerce');

            wp_send_json_error([
               'message' => $message
            ]);

         }

      }else{

         wp_send_json_error([
            'message' => __('Order not found.', 'convesiopay-woocommerce')
         ]);
      }

   }

}