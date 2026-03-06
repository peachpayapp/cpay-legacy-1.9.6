<?php
/**
 * Core Hook
 *
 * @author ConvesioPay - based on the Woosa WC integration
 */

namespace Woosa\Adyen;

use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Core_Hook implements Interface_Hook{

   /**
    * Stub function to prevent fatal errors from missing function hooks
    * This function was removed from the codebase but may still be registered in the database
    */
   public static function order_cancelled_so_cancelcheck() {
      // This function is a stub to prevent fatal errors
      // The original function was removed but may still be registered as a WordPress hook
      return;
   }

   /**
    * Initiates the hooks.
    *
    * @return void
    */
   public static function init(){

      add_filter('woocommerce_payment_gateways', [__CLASS__, 'payment_gateway']);

      add_action('before_woocommerce_init', [__CLASS__, 'declare_cart_checkout_blocks_compatibility']);

      add_action( 'woocommerce_blocks_loaded', [__CLASS__, 'register_checkout_blocks'] );

   }



   /**
    * Adds new gateway to WooCommerce payments.
    *
    * @since 1.0.0
    * @param array $gateways
    * @return array
    */
   public static function payment_gateway($gateways) {

      
      
      $gateways[] = Credit_Card::class;
      
      
      
      
      
      
      
      $gateways[] = Googlepay::class;
      $gateways[] = Applepay::class;
      
      
      
      
      
      
      
      
      
      
      
      
      
      
      

      return $gateways;
   }



   /**
    * Declares cart checkout blocks compatibility.
    *
    * @return void
    */
   public static function declare_cart_checkout_blocks_compatibility() {

      if (class_exists( FeaturesUtil::class )) {

         FeaturesUtil::declare_compatibility('cart_checkout_blocks', DIR_BASENAME, true);

      }

   }



   /**
    * Register checkout blocks
    *
    * @return void
    */
   public static function register_checkout_blocks() {

      if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
         return;
      }

      add_action(
         'woocommerce_blocks_payment_method_type_registration',
         function(PaymentMethodRegistry $payment_method_registry) {

            $payment_method_registry->register(new Checkout_Blocks_Credit_Card);
            $payment_method_registry->register(new Checkout_Blocks_Ideal);
            $payment_method_registry->register(new Checkout_Blocks_MOLPay_ML);
            $payment_method_registry->register(new Checkout_Blocks_MOLPay_TH);
            $payment_method_registry->register(new Checkout_Blocks_Online_Banking_Poland);
            $payment_method_registry->register(new Checkout_Blocks_Google_Pay);
            $payment_method_registry->register(new Checkout_Blocks_ApplePay);
            $payment_method_registry->register(new Checkout_Blocks_Swish);
            $payment_method_registry->register(new Checkout_Blocks_Giropay);
            $payment_method_registry->register(new Checkout_Blocks_Paypal);
            $payment_method_registry->register(new Checkout_Blocks_Vipps);
            $payment_method_registry->register(new Checkout_Blocks_Klarna);
            $payment_method_registry->register(new Checkout_Blocks_Klarna_PayNow);
            $payment_method_registry->register(new Checkout_Blocks_Klarna_Account);
            $payment_method_registry->register(new Checkout_Blocks_Wechatpay);
            $payment_method_registry->register(new Checkout_Blocks_Sofort);
            $payment_method_registry->register(new Checkout_Blocks_Alipay);
            $payment_method_registry->register(new Checkout_Blocks_Bancontact);
            $payment_method_registry->register(new Checkout_Blocks_Bancontact_Mobile);
            $payment_method_registry->register(new Checkout_Blocks_Blik);
            $payment_method_registry->register(new Checkout_Blocks_Boleto);
            $payment_method_registry->register(new Checkout_Blocks_Grabpay_SG);
            $payment_method_registry->register(new Checkout_Blocks_Grabpay_MY);
            $payment_method_registry->register(new Checkout_Blocks_Grabpay_PH);
            $payment_method_registry->register(new Checkout_Blocks_Mobilepay);
            $payment_method_registry->register(new Checkout_Blocks_Trustly);

        }
      );
   }


}
