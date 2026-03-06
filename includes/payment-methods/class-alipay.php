<?php
/**
 * Alipay
 *
 * Payment type     : Wallet
 * Payment flow     : Redirect
 * Countries        : CN
 * Currencies       : AUD, CAD, CHF, CNY, CZK, DKK, EUR, GBP, HKD, JPY, MYR, NOK, NZD, RUB, SEK, SGD, THB, USD
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


class Alipay extends Ideal{


   /**
    * Constructor of this class.
    *
    * @param bool $init_hooks
    * @since 1.0.0
    */
   public function __construct($init_hooks = true){

      Abstract_Gateway::__construct($init_hooks);

   }


   /**
    * List of countries where is available.
    *
    * @since 1.1.0
    * @return array
    */
   public function available_countries(){

      return [
         'CN' => [
            'currencies' => ['AUD', 'CAD', 'CHF', 'CNY', 'CZK', 'DKK', 'EUR', 'GBP', 'HKD', 'JPY', 'MYR', 'NOK', 'NZD', 'RUB', 'SEK', 'SGD', 'THB', 'USD'],
         ],
      ];
   }



   /**
    * Gets default payment method title.
    *
    * @since 1.0.0
    * @return string
    */
   public function get_default_title(){
      return __('ConvesioPay - Alipay', 'convesiopay-woocommerce');
   }



   /**
    * Gets default payment method description.
    *
    * @since 1.1.0 - display supported countries
    * @since 1.0.0
    * @return string
    */
   public function get_default_description(){
      return $this->show_supported_country();
   }



   /**
    * Type of the payment method (e.g ideal, scheme. bcmc).
    *
    * @since 1.0.0
    * @return string
    */
   public function payment_method_type(){
      return 'alipay';
   }


   /**
    * Returns the payment method to be used for recurring payments
    *
    * @since 1.0.0
    * @return string
    */
   public function recurring_payment_method(){}



   /**
    * Validates extra added fields.
    *
    * @since 1.0.0
    * @return bool
    */
   public function validate_fields() {
      return Abstract_Gateway::validate_fields();
   }



   /**
    * Builds the required payment payload
    *
    * @since 1.1.0 - use parent function to get common data
    * @since 1.0.0
    * @param \WC_Order $order
    * @param string $reference
    * @return array
    */
   protected function build_payment_payload(\WC_Order $order, $reference){
      return Abstract_Gateway::build_payment_payload($order, $reference);
   }



   /**
    * Processes the payment.
    *
    * @since 1.0.0
    * @param int $order_id
    * @return array
    */
   public function process_payment($order_id) {

      Abstract_Gateway::process_payment($order_id);

      $order     = wc_get_order($order_id);
      $reference = $order_id;
      $payload   = $this->build_payment_payload($order, $reference);

      $response = $this->api->checkout()->send_payment($payload);

      if($response->status == 200){

         return $this->process_payment_result( $response, $order );

      }else{

         wc_add_notice($response->body->message, 'error');

         NoticeHandler::convert_notices_to_exceptions( 'woocommerce_rest_payment_error' );

      }

      return ['result' => 'failure'];

   }

}