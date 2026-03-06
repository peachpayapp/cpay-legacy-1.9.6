<?php
/**
 * Trustly
 *
 * Payment type     : Online Banking
 * Payment flow     : Redirect
 * Countries        : NO, LV, GB, FI, CZ, DK, SK, SE, ES, LT, EE
 * Currencies       : DKK, EUR, NOK, PLN, SEK
 * Recurring        : Only in Sweden
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


class Trustly extends Klarna{


   /**
    * Constructor of this class.
    * @param bool $init_hooks
    */
    public function __construct($init_hooks = true){

      Abstract_Gateway::__construct($init_hooks);

      $this->supports = [
         'products',
         'refunds',
      ];

   }


   /**
    * List of countries where is available.
    *
    * @return array
    */
   public function available_countries(){

      return [
         'NO' => [
            'currencies' => ['NOK'],
            'recurring' => false,
         ],
         'LV' => [
            'currencies' => ['EUR'],
            'recurring' => false,
         ],
         'GB' => [
            'currencies' => ['GBP'],
            'recurring' => false,
         ],
         'FI' => [
            'currencies' => ['EUR'],
            'recurring' => false,
         ],
         'CZ' => [
            'currencies' => ['CZK'],
            'recurring' => false,
         ],
         'DK' => [
            'currencies' => ['DKK'],
            'recurring' => false,
         ],
         'SK' => [
            'currencies' => ['EUR'],
            'recurring' => false,
         ],
         'SE' => [
            'currencies' => ['SEK'],
            'recurring' => true,
         ],
         'ES' => [
            'currencies' => ['EUR'],
            'recurring' => false,
         ],
         'LT' => [
            'currencies' => ['EUR'],
            'recurring' => false,
         ],
         'EE' => [
            'currencies' => ['EUR'],
            'recurring' => false,
         ],
      ];
   }



   /**
    * Gets default payment method title.
    *
    * @return string
    */
   public function get_default_title(){
      return __('ConvesioPay - Trustly', 'convesiopay-woocommerce');
   }



   /**
    * Gets default payment method description.
    *
    * @return string
    */
   public function get_default_description(){

      $output = sprintf(__('Shoppers can pay with Trustly when shopping online or in-store using our terminals. %s', 'convesiopay-woocommerce'), '<br/>'.$this->show_supported_country());
      // $output .= '<br/>'.$this->show_rec_supported_country();

      return $output;
   }



   /**
    * Type of the payment method (e.g ideal, scheme. bcmc).
    *
    * @return string
    */
   public function payment_method_type(){
      return 'trustly';
   }



   /**
    * Processes the payment.
    *
    * @param int $order_id
    * @return array
    */
   public function process_payment($order_id) {

      parent::process_payment($order_id);

      $order = wc_get_order($order_id);

      $payload = $this->maybe_recurring_payment($order_id, $order);

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
    * Recurring payment only fow Sweden
    *
    * @param $order_id
    * @param \WC_Order $order
    * @return array
    */
   protected function maybe_recurring_payment($order_id, $order) {

      $subscriptions    = $this->get_subscriptions_for_order( $order_id );
      $customer_country = WC()->customer->get_billing_country();

      //recurring payments
      if(count($subscriptions) > 0 && 'SE' === $customer_country){
         $recurr_reference = [];
         $subscription_ids = [];

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

      } else {

         $payload = $this->build_payment_payload( $order, $order_id );

      }

      return $payload;

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
