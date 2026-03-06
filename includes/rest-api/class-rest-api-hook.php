<?php
/**
 * REST API Hook
 *
 * @author ConvesioPay - based on the Woosa WC integration
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class REST_API_Hook implements Interface_Hook{


   /**
    * Initiates the hooks.
    *
    * @return void
    */
   public static function init(){

      add_action('rest_api_init', [__CLASS__, 'register_endpoints']);

   }



   /**
    * Registers endpoints.
    *
    * @return void
    */
   public static function register_endpoints(){

      register_rest_route(
         'wc-convesiopay',
         'payment-status',
         [
            'methods' => \WP_REST_Server::EDITABLE,
            'callback' => [__CLASS__, 'process_payment_notification'],
            'permission_callback' => '__return_true',
         ]
      );

      register_rest_route(
         'wc-convesiopay',
         'boleto-payment-status',
         [
            'methods' => \WP_REST_Server::EDITABLE,
            'callback' => [__CLASS__, 'process_boleto_payment_notification'],
            'permission_callback' => '__return_true',
         ]
      );

   }



   /**
    * Validate the Adyen signature
    *
    * @param \WP_REST_Request $request
    * @return bool
    */
   public static function validate_hmac($request) {

      $hmacKey = Option::get('hmac_key');

      if (empty($hmacKey)) {
         return true;
      }

      $dataToSign = REST_API::get_signature_data($request->get_params());

      if (false === $dataToSign) {
         $dataToSign = REST_API::get_body_signature_data((array)json_decode($request->get_body(), true));
      }

      $hmacSignature = Util::array($request->get_params())->get('additionalData_hmacSignature');

      if (empty($hmacSignature)) {

         $hmacSignature = Util::array((array)json_decode($request->get_body(), true))
            ->get('notificationItems/0/NotificationRequestItem/additionalData/hmacSignature', '');

      }

      $signature = base64_encode(hash_hmac('sha256', $dataToSign, pack("H*", $hmacKey), true));

      if ($hmacSignature !== $signature) {

         Util::wc_error_log('Notifications could not be authenticated, please check HMAC key!', __FILE__, __LINE__);

      }

      return $hmacSignature === $signature;

   }



   /**
    * Processes the Boleto payment notification.
    *
    * @since 1.0.3
    * @param \WP_REST_Request $request
    * @return string
    */
   public static function process_boleto_payment_notification($request){

      $data  = REST_API::get_payload_data( $request->get_params() );
      $order = wc_get_order($data->order_id);

      if(REST_API::is_authenticated($request) && $order instanceof \WC_Order){

         switch($data->event_code){

            case 'AUTHORISATION':

               if($data->success === 'true'){

                  Order::payment_completed($order, $data->psp_reference, [], $data->payment_method);

               }

               break;

            case 'OFFER_CLOSED':

               if($data->success === 'true'){

                  $order->update_status('cancelled');
                  $order->add_order_note(sprintf(
                     __('The payment amount of %s has been cancelled.', 'wc-convesiopay'),
                     wc_price($data->amount_value)
                  ));

               }

               break;


            default:

               if($data->success === 'true'){

                  $order->update_status('on-hold');
                  $order->add_order_note(__('Waiting for customer to pay.', 'wc-convesiopay'));

               }
         }

         //something went wrong with the payment
         if($data->success !== 'true'){
            Order::payment_failed($order, $data->psp_reference, __('The payment could not be processed.', 'wc-convesiopay'));
         }

         REST_API::log_webhook_request('boleto-payment-status', $request);
      }

      return '[accepted]';
   }



   /**
    * Updates payment reference if it has changed and updates transaction ID
    *
    * @param \WC_Order $order
    * @param string $new_psp_reference
    * @return void
    */
   private static function update_payment_reference_if_changed($order, $new_psp_reference) {
      $order->read_meta_data(true);
      $order_psp_reference = $order->get_meta('_'.PREFIX.'_payment_pspReference');

      if ($order_psp_reference !== $new_psp_reference) {
         $order->update_meta_data('_'.PREFIX.'_payment_pspReference', $new_psp_reference);
         // Update the transaction ID to reflect the new reference
         $order->set_transaction_id($new_psp_reference);
      }
   }

   /**
    * Processes the standard payment notification.
    *
    * @since 1.0.10 - update cached payment methods
    * @since 1.0.0
    * @param \WP_REST_Request $request
    * @return string
    */
   public static function process_payment_notification($request) {

      if(! REST_API::is_authenticated($request) ) {
         return '[declined]'; 
      }

      $data  = REST_API::get_payload_data( $request->get_params() );
      $order = wc_get_order($data->order_id);

      if($order instanceof \WC_Order){

         $capture_payment = get_option(PREFIX.'_capture_payment', 'immediate');

         switch($data->event_code){

            case 'AUTHORISATION':

               if($data->success === 'true'){

                  // Update payment reference if it has changed
                  self::update_payment_reference_if_changed($order, $data->psp_reference);

                  if('immediate' === $capture_payment && Service_Util::is_manual_payment($data->payment_method)){

                     do_action(PREFIX . '\rest_api_hook\authorisation\manual_immediate_capture', $data, $order);
                     $order->update_status('on-hold');
                     $order->add_order_note(__('Waiting for payment capture.', 'wc-convesiopay'));

                  }else{

                     do_action(PREFIX . '\rest_api_hook\authorisation\payment_completed', $data, $order);
                     Order::payment_completed($order, $data->psp_reference, $data->subscription_ids, $data->payment_method);

                  }

                  // Update order and subscription tokens when we have a valid recurring reference
                  if (REST_API::has_valid_recurring_reference($data)) {
                     REST_API::collect_recurring_reference($order, $data);
                  }

               }else{

                  //only for orders which where not created via checkout
                  if('checkout' !== $order->get_created_via()){

                     do_action(PREFIX . '\rest_api_hook\authorisation\payment_failed', $data, $order);
                     Order::payment_failed($order, $data->psp_reference, __('The payment could not be processed.', 'wc-convesiopay'));
                  }
               }

               break;


            case 'CANCELLATION': case 'OFFER_CLOSED':

               //only for orders which where not created via checkout
               if('checkout' !== $order->get_created_via()){

                  if($data->success === 'true'){

                        do_action(PREFIX . '\rest_api_hook\cancellation\success', $data, $order);
                        $order->update_status('cancelled');
                        $order->add_order_note(sprintf(
                           __('The payment amount of %s has been cancelled.', 'wc-convesiopay'),
                           wc_price($data->amount_value)
                        ));

                  }else{

                     do_action(PREFIX . '\rest_api_hook\cancellation\failure', $data, $order);
                     Order::payment_failed($order, $data->psp_reference, sprintf(
                        __('The payment amount of %s could not be cancelled.', 'wc-convesiopay'),
                        wc_price($data->amount_value)
                     ));
                  }
               }

               break;


            case 'CAPTURE':

               if($data->success === 'true'){

                  do_action(PREFIX . '\rest_api_hook\capture\payment_completed', $data, $order);
                  Order::payment_completed($order, $data->psp_reference, $data->subscription_ids, $data->payment_method);

                  // Update order and subscription recurring token on CAPTURE so renewals have token when webhook only sends CAPTURE
                  if ( ! empty($data->subscription_ids) && REST_API::has_valid_recurring_reference($data) ) {
                     REST_API::collect_recurring_reference($order, $data);
                  }

               }else{

                  do_action(PREFIX . '\rest_api_hook\capture\payment_failed', $data, $order);
                  Order::payment_failed($order, $data->psp_reference, __('The payment capture has failed.', 'wc-convesiopay'));
               }

               break;


            case 'CAPTURE_FAILED':

               do_action(PREFIX . '\rest_api_hook\capture_failed', $data, $order);
               Order::payment_failed($order, $data->psp_reference, __('The payment capture has failed.', 'wc-convesiopay'));

               break;


            case 'REFUND':

               if($data->success === 'true'){

                  // Update payment reference if it has changed (e.g., new pspReference for refund)
                  self::update_payment_reference_if_changed($order, $data->psp_reference);

                  //change to refunded if the total order has been refunded
                  if($data->amount_value === $order->get_total()){
                     $order->update_status('refunded');
                  }

                  do_action(PREFIX . '\rest_api_hook\refund\sucess', $data, $order);
                  
                  // Add detailed refund note with timestamp
                  $refund_timestamp = current_time('F j, Y @ g:i a');
                  $order->add_order_note(sprintf(
                     __('The payment amount of %s has been refunded on %s.', 'wc-convesiopay'),
                     wc_price($data->amount_value),
                     $refund_timestamp
                  ));

               }else{

                  do_action(PREFIX . '\rest_api_hook\refund\failure', $data, $order);
                  Order::payment_failed($order, $data->psp_reference, sprintf(
                     __('Refunding the payment amount of %s has failed.', 'wc-convesiopay'),
                     wc_price($data->amount_value)
                  ));
               }

               break;


            case 'REFUND_FAILED':

               do_action(PREFIX . '\rest_api_hook\refund_failed', $data, $order);
               Order::payment_failed($order, $data->psp_reference, sprintf(
                  __('Refunding the payment amount of %s has failed.', 'wc-convesiopay'),
                  wc_price($data->amount_value)
               ));

               break;


            case 'CANCEL_OR_REFUND':

               if($data->success === 'true'){

                  // Update payment reference if it has changed
                  self::update_payment_reference_if_changed($order, $data->psp_reference);

                  do_action(PREFIX . '\rest_api_hook\cancel_or_refund\success', $data, $order);
                  
                  // Add detailed refund note with timestamp
                  $refund_timestamp = current_time('F j, Y @ g:i a');
                  $order->add_order_note(sprintf(
                     __('The payment amount of %s has been refunded on %s.', 'wc-convesiopay'),
                     wc_price($data->amount_value),
                     $refund_timestamp
                  ));

               }else{

                  do_action(PREFIX . '\rest_api_hook\cancel_or_refund\failure', $data, $order);
                  Order::payment_failed($order, $data->psp_reference, sprintf(
                     __('Refunding the payment amount of %s could not be refunded.', 'wc-convesiopay'),
                     wc_price($data->amount_value)
                  ));
               }

               break;


            case 'RECURRING_CONTRACT':

               if($data->success === 'true'){

                  do_action(PREFIX . '\rest_api_hook\recurring_contract', $data, $order);
                  REST_API::collect_recurring_reference($order, $data);

               }

               break;


            case 'REPORT_AVAILABLE':

               Util::wc_debug_log($data);

               break;


            default:

               do_action(PREFIX . '\rest_api_hook\unexpected_event', $data, $order);

               break;
         }


         //update the cached payment methods for credit card payment method, in this way we ensure the new stored cards will be also included
         if( in_array( $order->get_payment_method(), ['woosa_adyen_bancontact', 'woosa_adyen_credit_card'] ) ){
            Core::update_cached_payment_methods();
         }

         REST_API::log_webhook_request('payment-status', $request);
      }

      return '[accepted]';
   }

}