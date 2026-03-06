<?php
/**
 * Vipps
 *
 * Payment type     : Wallet
 * Payment flow     : Redirect
 * Countries        : NO
 * Currencies       : NOK
 * Recurring        : Yes
 * Refunds          : Yes
 * Partial refunds  : Yes
 * Separate captures: Yes
 * Chargebacks      : Yes
 * @since 1.5.0
 * @author ConvesioPay - based on the Woosa WC integration
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
use Automattic\WooCommerce\StoreApi\Utilities\NoticeHandler;

defined( 'ABSPATH' ) || exit;


class Vipps extends Abstract_Gateway{


   /**
    * Constructor of this class.
    *
    * @param bool $init_hooks
    * @since 1.5.0
    */
    public function __construct($init_hooks = true){

      parent::__construct($init_hooks);

      $this->supports = array_merge($this->supports, [
         'subscriptions',
         'subscription_cancellation',
         'subscription_suspension',
         'subscription_reactivation',
         'subscription_amount_changes',
         'subscription_date_changes',
         'multiple_subscriptions'
      ]);
   }



   /**
    * List of countries where is available.
    *
    * @since 1.5.0
    * @return array
    */
   public function available_countries(){

      return [
         'NO' => [
            'currencies' => ['NOK'],
         ],
      ];
   }



   /**
    * Gets default payment method title.
    *
    * @since 1.5.0
    * @return string
    */
   public function get_default_title(){
      return __('ConvesioPay - Vipps', 'convesiopay-woocommerce');
   }



   /**
    * Gets default payment method description.
    *
    * @since 1.5.0
    * @return string
    */
   public function get_default_description(){
      return sprintf(__('To use Vipps, the shopper needs to have the Vipps app installed on their mobile device, and a card (either Visa or Mastercard) linked to their Vipps account. The shopper\'s account information is connected to their phone number. %s', 'convesiopay-woocommerce'), '<br/>'.$this->show_supported_country());
   }



   /**
    * Gets default description set in settings.
    *
    * @since 1.5.0
    * @return string
    */
   public function get_settings_description(){}



   /**
    * Type of the payment method (e.g ideal, scheme. bcmc).
    *
    * @since 1.5.0
    * @return string
    */
   public function payment_method_type(){
      return 'vipps';
   }



   /**
    * Returns the payment method to be used for recurring payments
    *
    * @since 1.5.0
    * @return string
    */
   public function recurring_payment_method(){
      return 'vipps';
   }



   /**
    * Processes the payment.
    *
    * @since 1.5.0
    * @param int $order_id
    * @return array
    */
   public function process_payment($order_id) {

      parent::process_payment($order_id);

      $order     = wc_get_order($order_id);
      $reference = $order_id;
      $payload   = $this->build_payment_payload( $order, $reference );

      $recurr_reference = [];
      $subscriptions    = $this->get_subscriptions_for_order( $order_id );
      $subscription_ids = [];

      //recurring payments
      if(count($subscriptions) > 0){

         foreach($subscriptions as $sub_id => $item){
            $subscription_ids[$sub_id] = $sub_id;
            $recurr_reference[] = $sub_id;
         }

         $reference = \implode('-S', $recurr_reference);
         $reference = $order_id.'-S'.$reference;
         $payload = $this->build_payment_payload( $order, $reference );

         //for tokenizing
         $payload['storePaymentMethod'] = true;

         //create a list with unpaid subscriptions
         $order->update_meta_data('_'.PREFIX.'_unpaid_subscriptions', $subscription_ids);

      }

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
    * @since 1.2.0
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