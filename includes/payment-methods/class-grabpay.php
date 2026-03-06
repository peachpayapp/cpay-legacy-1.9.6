<?php
/**
 * Grabpay
 *
 * Payment type     : Wallet|PayLater
 * Payment flow     : Redirect
 * Countries        : -
 * Currencies       : -
 * Recurring        : No
 * Refunds          : Yes
 * Partial refunds  : Yes
 * Separate captures: No
 * Chargebacks      : No
 *
 * @author ConvesioPay - based on the Woosa WC integration
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
use Automattic\WooCommerce\StoreApi\Utilities\NoticeHandler;

defined( 'ABSPATH' ) || exit;


class Grabpay extends Abstract_Gateway{


   /**
    * Constructor of this class.
    * @param bool $init_hooks
    */
   public function __construct($init_hooks = true){

      parent::__construct($init_hooks);

      $this->has_fields   = false;
   }



   /**
    * Gets default payment method title.
    *
    * @return string
    */
   public function get_default_title(){
      return __('ConvesioPay - GrabPay', 'convesiopay-woocommerce');
   }



   /**
    * Gets default payment method description.
    *
    * @return string
    */
   public function get_default_description(){
      return sprintf(__('To pay with GrabPay, you redirect the shopper to the GrabPay login page from your website or app. The shopper authenticates using their phone number and a one-time password (OTP) that they received from Grab on their phone. %s', 'convesiopay-woocommerce'), '<br/>'.$this->show_supported_country());
   }



   /**
    * Gets default description set in settings.
    *
    * @return string
    */
   public function get_settings_description(){}



   /**
    * Type of the payment method (e.g ideal, scheme. bcmc).
    *
    * @return string
    */
   public function payment_method_type(){
      return 'grabpay';
   }


   public function recurring_payment_method(){}



   /**
    * Processes the payment.
    *
    * @param int $order_id
    * @return array
    */
   public function process_payment($order_id) {

      parent::process_payment($order_id);

      $order     = wc_get_order($order_id);
      $reference = $order_id;
      $payload   = $this->build_payment_payload( $order, $reference );

      $response = $this->api->checkout()->send_payment($payload);

      if($response->status == 200){

         return $this->process_payment_result( $response, $order );

      }else{

         wc_add_notice($response->body->message, 'error');

         NoticeHandler::convert_notices_to_exceptions( 'woocommerce_rest_payment_error' );

      }

      return ['result' => 'failure'];

   }


   /**
    * Processes the payment result.
    *
    * @param object $response
    * @param \WC_Order $order
    * @return array
    */
   protected function process_payment_result( $response, $order ){

      $body        = Util::obj_to_arr($response->body);
      $result_code = Util::array($body)->get('resultCode');
      $action      = Util::array($body)->get('action');

      $result = [
         'result'   => 'success',
         'redirect' => Service_Util::get_return_page_url($order, $result_code)
      ];

      $order->update_meta_data('_' . PREFIX . '_payment_resultCode', $result_code);
      $order->update_meta_data('_' . PREFIX . '_payment_action', $action);
      $order->save();

      if( 'RedirectShopper' == $result_code ){

         //redirect to process payment action via Web Component
         $result = [
            'result'   => 'success',
            'redirect' => add_query_arg(
               [
                  PREFIX . '_payment_method' => $this->payment_method_type(),
                  PREFIX . '_order_id'       => $order->get_id(),
               ],
               Service_Util::get_checkout_url($order)
            )
         ];

      }

      return $result;

   }


}