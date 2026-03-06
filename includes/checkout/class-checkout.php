<?php
/**
 * Checkout
 *
 * @author ConvesioPay - based on the Woosa WC integration
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Checkout{


   /**
    * Checks if checkout contains at least one subscription.
    *
    * @since 1.0.9 - added support for variable subscription
    *              - change name to `has_subscription`
    * @since 1.0.3
    * @return bool
    */
   public static function has_subscription(){

      if( WC()->cart ){

         foreach(WC()->cart->get_cart() as $item){
            if( $item['data']->is_type(['subscription_variation', 'subscription'])){
               return true;
            }
         }
      }

      return false;
   }
}