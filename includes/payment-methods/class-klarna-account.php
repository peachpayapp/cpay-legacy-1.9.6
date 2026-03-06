<?php
/**
 * Klarna (Pay over time)
 *
 * Payment type     : Buy Now Pay Later
 * Payment flow     : Redirect
 * Countries        : AU, AT, FI, DE, IT, NO, ES, SE, GB, US
 * Currencies       : AUD, EUR, SEK, GBP, USD
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


class Klarna_Account extends Klarna{


   /**
    * List of countries where is available.
    *
    * @since 1.1.0
    * @return array
    */
   public function available_countries(){

      return [
         'AU' => [
            'currencies' => ['AUD'],
            'recurring' => false,
         ],
         'AT' => [
            'currencies' => ['EUR'],
            'recurring' => false,
         ],
         'FI' => [
            'currencies' => ['EUR'],
            'recurring' => false,
         ],
         'DE' => [
            'currencies' => ['EUR'],
            'recurring' => false,
         ],
         'IT' => [
            'currencies' => ['EUR'],
            'recurring' => false,
         ],
         'NO' => [
            'currencies' => ['NOK'],
            'recurring' => false,
         ],
         'ES' => [
            'currencies' => ['EUR'],
            'recurring' => false,
         ],
         'SE' => [
            'currencies' => ['SEK'],
            'recurring' => false,
         ],
         'GB' => [
            'currencies' => ['GBP'],
            'recurring' => false,
         ],
         'US' => [
            'currencies' => ['USD'],
            'recurring' => true,//Could not find an acquirer account for the provided currency (USD)
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
      return __('ConvesioPay - Klarna - Pay over time', 'convesiopay-woocommerce');
   }



   /**
    * Gets default payment method description.
    *
    * @since 1.2.0 - show supported countries for recurring payments.
    * @since 1.1.0
    * @return string
    */
   public function get_default_description(){

      $output = sprintf(__('Pay in installments over time. %s', 'convesiopay-woocommerce'), '<br/>'.$this->show_supported_country());
      $output .= '<br/>'.$this->show_rec_supported_country();

      return $output;
   }



   /**
    * Type of the payment method (e.g ideal, scheme. bcmc).
    *
    * @since 1.1.0
    * @return string
    */
   public function payment_method_type(){
      return 'klarna_account';
   }


}