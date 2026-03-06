<?php
/**
 * Orders
 *
 * @author ConvesioPay - based on the Woosa WC integration
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Order{


   /**
    * Adds the prefix to the given reference.
    *
    * @since 1.1.0
    * @param string $reference
    * @return string
    */
   public static function add_reference_prefix($reference){

      $prefix = get_option(PREFIX . '_order_reference_prefix');
      $prefix = empty($prefix) ? '' : "{$prefix}-";

      return $prefix.$reference;

   }



   /**
    * Removes the prefix from the given reference.
    *
    * @since 1.1.0
    * @param string $reference
    * @return string
    */
   public static function remove_reference_prefix($reference){

      $prefix = get_option(PREFIX . '_order_reference_prefix');
      $prefix = empty($prefix) ? '' : "{$prefix}-";

      return str_replace("{$prefix}", '', $reference);

   }



   /**
    * Sets order according to the payment result.
    *
    * @since 1.3.0
    * @param \WC_Order $order
    * @param object $response - request response
    * @return void
    */
   public static function payment_result(\WC_Order $order, $response){

      $statuses = [
         'Authorised' => 'complete',
         // 'Refused'    => 'failed',
         // 'Error'      => 'failed',
         // 'Cancelled'  => 'cancelled',
         // 'Pending'    => 'pending',
         'Received'  => 'on-hold',
      ];

      if($response->status == 200){

         $body           = Util::obj_to_arr($response->body);
         $result_code    = Util::array($body)->get('resultCode');
         $psp_reference  = Util::array($body)->get('pspReference');
         $status         = Util::array($statuses)->get($result_code);
         $redirect_url   = Service_Util::get_return_page_url($order, $result_code);
         $payment_method = str_replace('woosa_adyen_', '', $order->get_payment_method());

         if( 'processing' === $status && Service_Util::is_manual_payment($payment_method) ){
            $status = 'on-hold';
         }

         if('complete' === $status){

            if (!self::is_order_payment_completed($order->get_id())) {

               self::set_order_payment_completed($order->get_id());
               $order->payment_complete($psp_reference);
               $order->add_order_note(sprintf(__('Order completed using %s .', 'convesiopay-woocommerce'), $order->get_payment_method_title()));

            }

         }elseif( ! empty($status) ){
            $order->set_transaction_id($psp_reference);
            $order->set_status($status);
         }

         if ('Error' === $result_code || 'Refused' === $result_code) {

            $refusal_reason = Util::array($body)->get('refusalReason');

            if (!empty($refusal_reason)) {

               $order->add_order_note(sprintf(__('The payment failed due to: %s', 'convesiopay-woocommerce'), $refusal_reason));

            }


         }

         if( ! empty($psp_reference) ){
            $order->read_meta_data();

            $order_psp_reference = $order->get_meta('_'.PREFIX.'_payment_pspReference');

            if ($order_psp_reference !== $psp_reference) {

               $order->update_meta_data('_'.PREFIX.'_payment_pspReference', $psp_reference);

            }

         }

      }else{

         $order->add_order_note(sprintf(__('The payment did not succeed. Request response: %s', 'convesiopay-woocommerce'),  json_encode($response->body)));

         $redirect_url = $order->get_checkout_payment_url();
      }

      $order->save();

      $redirect_url = apply_filters(PREFIX . '\order\payment_result', $redirect_url, $order, $result_code);

      wp_redirect($redirect_url);
      exit;
   }



   /**
    * Sets order as payment failed
    *
    * @since 1.0.0
    * @param \WC_Order $order
    * @param string $reference - payment reference
    * @param string $message
    * @return void
    */
   public static function payment_failed($order, $reference, $message){

      if( ! $order->has_status('failed')){
         $order->set_status('failed');
         $order->add_order_note($message);
      }

      $order->set_transaction_id($reference);
      $order->save();
   }



   /**
    * Sets the order as payment completed
    *
    * @since 1.0.0
    * @param \WC_Order $order
    * @param string $reference - payment reference
    * @param array $subscription_ids - list of subscription ids
    * @return void
    */
   public static function payment_completed($order, $reference, $subscription_ids, $payment_method){

      $unpaid_subscriptions = array_filter( (array) $order->get_meta('_'.PREFIX.'_unpaid_subscriptions') );

      $order->update_meta_data('_'.PREFIX.'_payment_captured', 'yes');

      //process subscriptions
      foreach($subscription_ids as $sub_id){

         if( isset($unpaid_subscriptions[$sub_id]) ){

            if(class_exists('\WC_Subscription')){

               $subscription = new \WC_Subscription($sub_id);
               $subscription->set_status('active');
               $subscription->save();

               unset($unpaid_subscriptions[$sub_id]);

               $order->update_meta_data('_'.PREFIX.'_unpaid_subscriptions', $unpaid_subscriptions);
            }

         }
      }


      //mark order as completed as long as there are no unpaid subscriptions
      if( count($unpaid_subscriptions) == 0 ){

         //set order payment method via SEPA for recurring payments
         if( count($subscription_ids) > 0 && $payment_method === 'sepadirectdebit' ){

            $sepa_method_id = 'woosa_adyen_sepa_direct_debit';
            $sepa_settings = get_option("woocommerce_{$sepa_method_id}_settings");

            $order->set_payment_method($sepa_method_id);
            $order->set_payment_method_title( Util::array($sepa_settings)->get('title') );
         }

         if (!self::is_order_payment_completed($order->get_id())) {

            self::set_order_payment_completed($order->get_id());

            $order->payment_complete( $reference );
            $order->add_order_note(sprintf(__('Order completed using %s .', 'convesiopay-woocommerce'), $order->get_payment_method_title()));

         }
      }

      $order->save();
   }



   /**
    * Check if the order payment is completed
    *
    * @param $order_id
    * @return bool
    */
   public static function is_order_payment_completed($order_id) {

      $payment_completed = wp_cache_get(PREFIX . '_order_'.$order_id.'_payment_completed', PREFIX, true);

      if (empty($payment_completed)) {
         $payment_completed = Transient::get('order_'.$order_id.'_payment_completed');
      }

      if (empty($payment_completed)) {
         return false;
      }

      return Util::string_to_bool($payment_completed);
   }



   /**
    * Set order payment is completed
    *
    * @param $order_id
    * @return void
    */
   public static function set_order_payment_completed($order_id) {

      wp_cache_set(PREFIX . '_order_'.$order_id.'_payment_completed', 'yes', PREFIX, HOUR_IN_SECONDS);

      Transient::set('order_'.$order_id.'_payment_completed', 'yes', HOUR_IN_SECONDS);

   }


}