<?php
/**
 * Core Hook AJAX
 *
 * @author ConvesioPay - based on the Woosa WC integration
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Core_Hook_AJAX implements Interface_Hook{


   /**
    * Initiates the hooks.
    *
    * @return void
    */
   public static function init(){

      add_action('wp_ajax_'.PREFIX.'_capture_payment', [__CLASS__, 'capture_payment']);
   }



   /**
    * Captures payments.
    *
    * @since 1.0.3
    * @return string
    */
   public static function capture_payment(){

      //check to make sure the request is from same server
      if(!check_ajax_referer( 'wsa-nonce', 'security', false )){
         return;
      }

      $order_id  = Util::array($_POST)->get('order_id');
      $order     = wc_get_order($order_id);
      $reference = $order->get_meta('_'.PREFIX.'_payment_pspReference');
      $amount    = $order->get_total();

      $response = Service::checkout()->capture_payment($reference, $amount);

      if( $response->status == 201 ){

         $order->update_meta_data('_'.PREFIX.'_payment_captured', 'yes');

         $order->payment_complete( $reference );
         $order->add_order_note( __('The payment has been successfully captured.', 'convesiopay-woocommerce') );
         $order->save();

         wp_send_json_success();

      }else{

         wp_send_json_error();
      }


   }

}