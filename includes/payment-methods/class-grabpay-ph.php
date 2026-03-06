<?php
/**
 * GrabPay - Philippines
 *
 * Payment type     : Wallet|PayLater
 * Payment flow     : Redirect
 * Countries        : PH
 * Currencies       : PHP
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
defined( 'ABSPATH' ) || exit;


class Grabpay_PH extends Grabpay{


   /**
    * List of countries where is available.
    *
    * @return array
    */
   public function available_countries(){

      return [
         'PH' => [
            'currencies' => ['PHP'],
         ],
      ];
   }



   /**
    * Gets default payment method title.
    *
    * @return string
    */
   public function get_default_title(){
      return __('ConvesioPay - GrabPay - Philippines', 'convesiopay-woocommerce');
   }



   /**
    * Type of the payment method (e.g ideal, scheme. bcmc).
    *
    * @return string
    */
   public function payment_method_type(){
      return 'grabpay_PH';
   }


}