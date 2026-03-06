<?php
/**
 * Giropay
 *
 * Payment type     : Online banking
 * Payment flow     : Redirect
 * Countries        : DE
 * Currencies       : EUR
 * Recurring        : Yes via SEPA
 * Refunds          : Yes
 * Separate captures: No
 * Chargebacks      : No
 *
 * @author ConvesioPay - based on the Woosa WC integration
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Giropay extends Ideal{


   /**
    * Constructor of this class.
    *
    * @param bool $init_hooks
    * @since 1.1.1
    */
   public function __construct($init_hooks = true){

      parent::__construct($init_hooks);

      $this->has_fields = false;

   }



   /**
    * List of countries where is available.
    *
    * @since 1.1.0
    * @return array
    */
   public function available_countries(){

      return [
         'DE' => [
            'currencies' => ['EUR'],
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
      return __('ConvesioPay - Giropay', 'convesiopay-woocommerce');
   }



   /**
    * Gets default payment method description.
    *
    * @since 1.1.0 - display supported countries
    * @since 1.0.0
    * @return string
    */
   public function get_default_description(){
      return sprintf(__('In order to support recurring payments with Giropay you have to enable SEPA Direct Debit first. %s', 'convesiopay-woocommerce'), '<br/>'.$this->show_supported_country());
   }



   /**
    * Type of the payment method (e.g ideal, scheme. bcmc).
    *
    * @since 1.0.0
    * @return string
    */
   public function payment_method_type(){
      return 'giropay';
   }



   /**
    * Returns the payment method to be used for recurring payments
    *
    * @since 1.0.0
    * @return string
    */
   public function recurring_payment_method(){
      return 'sepadirectdebit';
   }



   /**
    * Validates extra added fields.
    *
    * @since 1.1.1 - use the validation from the abstract class
    * @since 1.0.0
    * @return bool
    */
   public function validate_fields() {
      return Abstract_Gateway::validate_fields();
   }



   /**
    * Builds the required payment payload
    *
    * @since 1.1.1 - remove BIC field
    * @since 1.1.0 - use parent function to get common data
    * @since 1.0.0
    * @param \WC_Order $order
    * @param string $reference
    * @return array
    */
   protected function build_payment_payload(\WC_Order $order, $reference){
      return Abstract_Gateway::build_payment_payload($order, $reference);
   }

}