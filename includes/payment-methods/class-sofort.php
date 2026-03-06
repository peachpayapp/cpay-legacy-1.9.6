<?php
/**
 * Sofort
 *
 * Payment type     : Online banking
 * Payment flow     : Redirect
 * Countries        : AT, BE, DE, IT, ES, CH, NL, GB
 * Currencies       : EUR, CHF, GBP
 * Recurring        : Yes via SEPA
 * Refunds          : Yes
 * Partial refunds  : Yes
 * Separate captures: no
 * Chargebacks      : Yes
 *
 * @author ConvesioPay - based on the Woosa WC integration
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Sofort extends Ideal{


   /**
    * Constructor of this class.
    *
    * @param bool $init_hooks
    * @since 1.0.0
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
         'AT' => [
            'currencies' => ['EUR'],
         ],
         'BE' => [
            'currencies' => ['EUR'],
         ],
         'DE' => [
            'currencies' => ['EUR'],
         ],
         'IT' => [
            'currencies' => ['EUR'],
         ],
         'ES' => [
            'currencies' => ['EUR'],
         ],
         'CH' => [
            'currencies' => ['CHF'],
         ],
         'NL' => [
            'currencies' => ['EUR'],
         ],
         'GB' => [
            'currencies' => ['GBP'],
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
      return __('ConvesioPay - Sofort', 'convesiopay-woocommerce');
   }



   /**
    * Gets default payment method description.
    *
    * @since 1.1.0 - display supported countries
    * @since 1.0.0
    * @return string
    */
   public function get_default_description(){
      return sprintf(__('In order to support recurring payments with Sofort you have to enable SEPA Direct Debit first. %s', 'convesiopay-woocommerce'), '<br/>'.$this->show_supported_country());
   }



   /**
    * Gets default description set in settings.
    *
    * @since 1.0.0
    * @return string
    */
   public function get_settings_description(){}



   /**
    * Type of the payment method (e.g ideal, scheme. bcmc).
    *
    * @since 1.0.0
    * @return string
    */
   public function payment_method_type(){
      return 'directEbanking';
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


}