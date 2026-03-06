<?php
/**
 * REST API
 *
 * @author ConvesioPay - based on the Woosa WC integration
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class REST_API{


   /**
    * Checks whether or not the authentication worked.
    *
    * @since 1.0.0
    * @param \WP_REST_Request $request
    * @return boolean
    */
   public static function is_authenticated($request) {

      $signature = $request->get_header('X-ConvesioPay-Signature');
      $authHeader = $signature ? $signature : $request->get_header('authorization');
      $authorization = base64_decode(str_replace('Basic ', '', $authHeader));
      $credentials      = explode(':', $authorization);
      $username = Option::get('api_username');
      $password = Option::get('api_password');
      $provided_username = $credentials[0] ?? '';
      $provided_password = $credentials[1] ?? '';

      if ( apply_filters(PREFIX . '\rest_api\is_authenticated\force_full_authentication', false) && ( empty($username) || empty($password) ) ) {
         return false;
      }

      if( !empty($username) || !empty($password) ) {

         if ( $username != ($credentials[0] ?? null) || $password != ($credentials[1] ?? null) ) {

            if ( $provided_username != $username || $provided_password != $password ) {
               Util::wc_error_log([
                  'message' => 'ConvesioPay webhook authentication failed - invalid credentials',
                  'provided_credentials' => [
                     'username' => $provided_username,
                     'password' => '***' // Don't log the actual password for security
                  ],
                  'request' => [
                     'headers' => $request->get_headers(),
                  ]
               ], __FILE__, __LINE__);
               return false;
            }

         }

      }

      return true;
   }



   /**
    * Returns an object with the payload data.
    *
    * @since 1.1.0 - remove the order reference prefix
    *              - add `-S` as a new reference separator for subscriptions
    * @since 1.0.3
    * @param array $payload
    * @return object
    */
   public static function get_payload_data($payload){

      $psp_reference    = Util::array($payload)->get('pspReference');
      $recurr_reference = Util::array($payload)->get('additionalData_recurring_recurringDetailReference');
      $event_code       = Util::array($payload)->get('eventCode');
      $success          = Util::array($payload)->get('success');
      $reason           = Util::array($payload)->get('reason');
      $value            = (float) Util::array($payload)->get('value');
      $amount_value     = number_format($value/100, 2, '.', '');
      $payment_method   = Util::array($payload)->get('paymentMethod');
      $order_id         = Util::array($payload)->get('merchantReference');
      $subscription_ids = [];

      //keep support for old `_#subscription#_`
      $seps = ['_#subscription#_', '-S'];

      foreach($seps as $sep){

         if(strpos($order_id, $sep) !== false){

            $rfs = explode($sep, $order_id);
            $order_id = $rfs[0];

            //remove order id
            unset($rfs[0]);

            $subscription_ids = $rfs;
         }

      }

      $data = [
         'psp_reference'    => $psp_reference,
         'recurr_reference' => $recurr_reference,
         'event_code'       => $event_code,
         'success'          => $success,
         'reason'           => $reason,
         'value'            => $value,
         'amount_value'     => $amount_value,
         'payment_method'   => $payment_method,
         'order_id'         => Order::remove_reference_prefix($order_id),
         'subscription_ids' => $subscription_ids,
      ];

      return (object) apply_filters(PREFIX . '\rest_api\payload_data', $data, $payload);
   }



   /**
    * Get the data used for the signature
    *
    * @param array $payload
    * @return string
    */
   public static function get_signature_data($payload) {

      $signature_data = [
         'pspReference'	=> self::sanitize(Util::array($payload)->get('pspReference', '')),
         'originalReference' => self::sanitize(Util::array($payload)->get('originalReference', '')),
         'merchantAccountCode' => self::sanitize(Util::array($payload)->get('merchantAccountCode', '')),
         'merchantReference' => self::sanitize(Util::array($payload)->get('merchantReference', '')),
         'value'	 => self::sanitize(Util::array($payload)->get('value', '')),
         'currency'	 => self::sanitize(Util::array($payload)->get('currency', '')),
         'eventCode' => self::sanitize(Util::array($payload)->get('eventCode', '')),
         'success'	 => self::sanitize(Util::array($payload)->get('success', '')),
      ];

      if (empty(array_filter(array_values($signature_data)))) {
         return false;
      }

      return implode(':', array_values($signature_data));
   }



   /**
    * Get the data used for the signature
    *
    * @param array $body_payload
    * @return string
    */
   public static function get_body_signature_data($body_payload) {

      $payload = Util::array($body_payload)->get('notificationItems/0/NotificationRequestItem', []);

      $signature_data = [
         'pspReference'	=> self::sanitize(Util::array($payload)->get('pspReference', '')),
         'originalReference' => self::sanitize(Util::array($payload)->get('originalReference', '')),
         'merchantAccountCode' => self::sanitize(Util::array($payload)->get('merchantAccountCode', '')),
         'merchantReference' => self::sanitize(Util::array($payload)->get('merchantReference', '')),
         'value'	 => self::sanitize(Util::array($payload)->get('amount/value', '')),
         'currency'	 => self::sanitize(Util::array($payload)->get('amount/currency', '')),
         'eventCode' => self::sanitize(Util::array($payload)->get('eventCode', '')),
         'success'	 => self::sanitize(Util::array($payload)->get('success', '')),
      ];

      if (empty(array_filter(array_values($signature_data)))) {
         return false;
      }

      return implode(':', array_values($signature_data));
   }



   /**
    * Sanitize the signature item
    *
    * @param $val
    * @return array|string|string[]
    */
   public static function sanitize($val) {
      return str_replace(':', '\\:', str_replace('\\', '\\\\', $val));
   }



   /**
    * Collects the recurring reference for a given order and webhook data.
    *
    * @param \WC_Order $order
    * @param object $args
    * @return void
    */
   public static function collect_recurring_reference(\WC_Order $order, $args){

      $shopper_reference = $order->get_meta('_'.PREFIX.'_shopper_reference');

      foreach($args->subscription_ids as $sub_id){

         $recurr_reference = empty($args->recurr_reference) ? $args->psp_reference : $args->recurr_reference;

         $subscription = wc_get_order($sub_id);
         $subscription->update_meta_data('_'.PREFIX.'_recurringDetailReference', $recurr_reference);

         if ( ! $subscription->meta_exists('_'.PREFIX.'_shopper_reference')) {
            $subscription->add_meta_data('_'.PREFIX.'_shopper_reference', $shopper_reference, true);
         }

         $subscription->save();

      }
   }



   /**
    * Logs the webhook received request.
    *
    * @param string $endpoint
    * @param \WP_REST_Request $request
    * @param mixed $response
    * @return void
    */
   public static function log_webhook_request($endpoint, \WP_REST_Request $request, $response = '[accepted]'){

      $headers = [];

      foreach($request->get_headers() as $key => $val){
         $headers[$key] = $val[0];
      }

      if(DEBUG){
         Util::wc_debug_log([
            'title'   => '==== REST-API - WEBHOOK NOTIFICATION ====',
            'data'    => [
               'request' => [
                  'endpoint' => rest_url("wc-convesiopay/{$endpoint}"),
                  'headers'  => $headers,
                  'body'     => $request->get_params()
               ],
               'response' => $response
            ]
         ], __FILE__, __LINE__);
      }
   }

}