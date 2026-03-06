<?php
/**
 * Klarna (Pay Now)
 *
 * Payment type     : Buy Now Pay Now
 * Payment flow     : Redirect
 * Countries        : AT, DE, SE, CH, NL
 * Currencies       : EUR, SEK, CHF
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


class Klarna_PayNow extends Klarna{


   /**
    * List of countries where is available.
    *
    * @since 1.2.0 - show supported countries for recurring payments.
    * @since 1.1.0
    * @return array
    */
   public function available_countries(){

      return [
         'AT' => [
            'currencies' => ['EUR'],
            'recurring' => false,
         ],
         'DE' => [
            'currencies' => ['EUR'],
            'recurring' => true,
         ],
         'SE' => [
            'currencies' => ['SEK'],
            'recurring' => true,
         ],
         'CH' => [
            'currencies' => ['CHF'],
            'recurring' => false,
         ],
         'NL' => [
            'currencies' => ['EUR'],
            'recurring' => true,
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
      return __('ConvesioPay - Klarna - Pay now', 'convesiopay-woocommerce');
   }



   /**
    * Gets default payment method description.
    *
    * @since 1.1.0
    * @return string
    */
   public function get_default_description(){

      $output = sprintf(__('Pay the whole amount instantly, either by online banking or direct debit. %s', 'convesiopay-woocommerce'), '<br/>'.$this->show_supported_country());
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
      return 'klarna_paynow';
   }


}