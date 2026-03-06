<?php
/**
 * Klarna (Pay later)
 *
 * Payment type     : Buy Now Pay Later
 * Payment flow     : Redirect
 * Countries        : AT, BE, DK, FI, DE, NO, SE, CH, NL, GB
 * Currencies       : EUR, DKK, NOK, SEK, CHF, GBP
 * Recurring        : Yes
 * Refunds          : Yes
 * Partial refunds  : Yes
 * Separate captures: Yes
 * Partial captures : Yes
 * Chargebacks      : Yes
 *
 * @author ConvesioPay - based on the Woosa WC integration
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Klarna extends Ideal{


   /**
    * Constructor of this class.
    *
    * @param bool $init_hooks
    * @since 1.1.0
    */
   public function __construct($init_hooks = true){

      parent::__construct($init_hooks);

      $this->has_fields = false;

      if( 'yes' !== get_option(PREFIX .'_auto_klarna_payments') ){

         $this->supports = [
            'products',
            'refunds',
         ];

      }

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
            'recurring' => true,
         ],
         'BE' => [
            'currencies' => ['EUR'],
            'recurring' => false,
         ],
         'DK' => [
            'currencies' => ['DKK'],
            'recurring' => true,
         ],
         'FI' => [
            'currencies' => ['EUR'],
            'recurring' => true,
         ],
         'DE' => [
            'currencies' => ['EUR'],
            'recurring' => true,
         ],
         'NO' => [
            'currencies' => ['NOK'],
            'recurring' => true,
         ],
         'SE' => [
            'currencies' => ['SEK'],
            'recurring' => true,
         ],
         'CH' => [
            'currencies' => ['CHF'],
            'recurring' => true,
         ],
         'NL' => [
            'currencies' => ['EUR'],
            'recurring' => true,
         ],
         'GB' => [
            'currencies' => ['GBP'],
            'recurring' => false,
         ],
      ];
   }



   /**
    * Gets default payment method title.
    *
    * @since 1.1.0
    * @return string
    */
   public function get_default_title(){
      return __('ConvesioPay - Klarna - Pay later', 'convesiopay-woocommerce');
   }



   /**
    * Gets default payment method description.
    *
    * @since 1.2.0 - show supported countries for recurring payments.
    * @since 1.1.0
    * @return string
    */
   public function get_default_description(){

      $output = sprintf(__('Pay after the goods have been delivered. %s', 'convesiopay-woocommerce'), '<br/>'.$this->show_supported_country());
      $output .= '<br/>'.$this->show_rec_supported_country();

      return $output;
   }



   /**
    * Gets default description set in settings.
    *
    * @since 1.1.0
    * @return string
    */
   public function get_settings_description(){}



   /**
    * Type of the payment method (e.g ideal, scheme. bcmc).
    *
    * @since 1.1.0
    * @return string
    */
   public function payment_method_type(){
      return 'klarna';
   }



   /**
    * Returns the payment method to be used for recurring payments
    *
    * @since 1.1.0
    * @return string
    */
   public function recurring_payment_method(){
      return $this->payment_method_type();
   }



   /**
    * Validates extra added fields.
    *
    * @since 1.1.0
    * @return bool
    */
   public function validate_fields() {
      return Abstract_Gateway::validate_fields();
   }



   /**
    * Builds the required payment payload
    *
    * @since 1.1.0
    * @param \WC_Order $order
    * @param string $reference
    * @return array
    */
   protected function build_payment_payload(\WC_Order $order, $reference){
      return Abstract_Gateway::build_payment_payload($order, $reference);
   }



   /**
    * Checks whether or not the recurring payments are supported by the country.
    *
    * @since 1.2.0
    * @return bool
    */
   public function support_recurring(){

      if( WC()->cart && $this->get_order_total() > 0 ) {

         if( ! empty($this->available_countries()) ){

            $customer_country = WC()->customer->get_billing_country();
            $country = Util::array($this->available_countries())->get($customer_country);

            return Util::array($country)->get('recurring') === true ? true : false;
         }
      }

      return true;
   }



   /**
    * Display the countries which support recurring payments.
    *
    * @since 1.2.0
    * @return array
    */
   public function show_rec_supported_country(){

      $output = '';
      $countries = [];

      foreach($this->available_countries() as $country_code => $data){

         $country_code = '_ANY_' === $country_code ? __('ANY', 'convesiopay-woocommerce') : $country_code;

         if(Util::array($data)->get('recurring') === true){
            if( empty(Util::array($data)->get('currencies')) ){
               $countries[] = $country_code;
            }else{
               $currencies = Util::array($data)->get('currencies');
               $countries[] = $country_code . ' ('.implode(', ', $currencies).')';
            }
         }
      }

      if( ! empty($countries) ){
         $output = sprintf(__('%sSupported country for recurring payments:%s %s', 'convesiopay-woocommerce'), '<b>', '</b>', implode(', ', $countries));
      }

      return $output;
   }


}