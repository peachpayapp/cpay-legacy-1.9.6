<?php
/**
 * MOLPay online banking
 *
 * Payment type     : Online banking
 * Payment flow     : Redirect
 * Countries        : TH
 * Currencies       : THB
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


class MOLPay_TH extends MOLPay{



   /**
    * List of countries where is available.
    *
    * @return array
    */
   public function available_countries(){

      return [
         'TH' => [
            'currencies' => ['THB'],
         ],
      ];
   }



   /**
    * Gets default payment method title.
    *
    * @return string
    */
   public function get_default_title(){
      return __('ConvesioPay - MOLPay - Thailand', 'convesiopay-woocommerce');
   }





   /**
    * Type of the payment method (e.g ideal, scheme. bcmc).
    *
    * @return string
    */
   public function payment_method_type(){
      return 'molpay_ebanking_TH';
   }

}