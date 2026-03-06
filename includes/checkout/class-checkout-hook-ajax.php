<?php
/**
 * Checkout Hook AJAX
 *
 * @author ConvesioPay - based on the Woosa WC integration
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Checkout_Hook_AJAX implements Interface_Hook{


   /**
    * Initiates the hooks.
    *
    * @return void
    */
   public static function init(){

      add_action('wp_ajax_nopriv_' . PREFIX . '_additional_details', [__CLASS__, 'handle_payment_details']);
      add_action('wp_ajax_' . PREFIX . '_additional_details', [__CLASS__, 'handle_payment_details']);
      add_action('wp_ajax_nopriv_' . PREFIX . '_get_payment_method_data', [__CLASS__, 'handle_get_payment_method_data']);
      add_action('wp_ajax_' . PREFIX . '_get_payment_method_data', [__CLASS__, 'handle_get_payment_method_data']);

   }



   /**
    * Processes the request to send payment details.
    *
    * @return string
    */
   public static function handle_payment_details(){

      //check to make sure the request is from same server
      if(!check_ajax_referer( 'wsa-nonce', 'security', false )){
         return;
      }

      $order_id     = Util::array($_POST)->get('order_id');
      $payload      = Util::array($_POST)->get('state_data');
      $order        = wc_get_order($order_id);
      $redirect_url = wc_get_endpoint_url( 'order-received', '', wc_get_page_permalink( 'checkout' ) );

      if($order instanceof \WC_Order){

         $response = Service::checkout()->send_payment_details($payload);

         if($response->status == 200){

            $body         = Util::obj_to_arr($response->body);
            $result_code  = Util::array($body)->get('resultCode');
            $redirect_url = Service_Util::get_return_page_url($order, $result_code);

         }else{

            $redirect_url = $order->get_checkout_payment_url();
         }

      }

      wp_send_json_success(['redirect' => $redirect_url]);
   }



   /**
    * Processes the request to get payment method data.
    *
    * @return string
    */
   public static function handle_get_payment_method_data(){

      //check to make sure the request is from same server
      if(!check_ajax_referer( 'wsa-nonce', 'security', false )){
         return;
      }

      $payment_method_name = Util::array($_POST)->get('payment_method');

      if (empty($payment_method_name)) {

         wp_send_json_error(['message' => __( 'Payment method parameter not provided!', 'wc-convesiopay' )], 401 );

      }

      $payment_methods = [
         'woosa_adyen_credit_card' => Checkout_Blocks_Credit_Card::class,
         'woosa_adyen_ideal' => Checkout_Blocks_Ideal::class,
         'woosa_adyen_molpay_ml' => Checkout_Blocks_MOLPay_ML::class,
         'woosa_adyen_molpay_th' => Checkout_Blocks_MOLPay_TH::class,
         'woosa_adyen_online_banking_poland' => Checkout_Blocks_Online_Banking_Poland::class,
         'woosa_adyen_googlepay' => Checkout_Blocks_Google_Pay::class,
         'woosa_adyen_swish' => Checkout_Blocks_Swish::class,
         'woosa_adyen_giropay' => Checkout_Blocks_Giropay::class,
         'woosa_adyen_paypal' => Checkout_Blocks_Paypal::class,
         'woosa_adyen_vipps' => Checkout_Blocks_Vipps::class,
         'woosa_adyen_klarna' => Checkout_Blocks_Klarna::class,
         'woosa_adyen_klarna_paynow' => Checkout_Blocks_Klarna_PayNow::class,
         'woosa_adyen_klarna_account' => Checkout_Blocks_Klarna_Account::class,
         'woosa_adyen_wechatpay' => Checkout_Blocks_Wechatpay::class,
         'woosa_adyen_sofort' => Checkout_Blocks_Sofort::class,
         'woosa_adyen_alipay' => Checkout_Blocks_Alipay::class,
         'woosa_adyen_bancontact' => Checkout_Blocks_Bancontact::class,
         'woosa_adyen_bancontact_mobile' => Checkout_Blocks_Bancontact_Mobile::class,
         'woosa_adyen_blik' => Checkout_Blocks_Blik::class,
         'woosa_adyen_boleto' => Checkout_Blocks_Boleto::class,
         'woosa_adyen_grabpay_sg' => Checkout_Blocks_Grabpay_SG::class,
         'woosa_adyen_grabpay_my' => Checkout_Blocks_Grabpay_MY::class,
         'woosa_adyen_grabpay_ph' => Checkout_Blocks_Grabpay_PH::class,
         'woosa_adyen_mobilepay' => Checkout_Blocks_Mobilepay::class,
         'woosa_adyen_trustly' => Checkout_Blocks_Trustly::class,
      ];

      if (!empty($payment_methods[$payment_method_name]) && class_exists($payment_methods[$payment_method_name])) {

         $payment_method = new $payment_methods[$payment_method_name];
         $payment_method->initialize();

         wp_send_json_success(['payment_method_data' => $payment_method->get_payment_method_data()]);

      }

      wp_send_json_error(['message' =>  __( 'Payment method not found!', 'wc-convesiopay' )], 401 );
   }


}
