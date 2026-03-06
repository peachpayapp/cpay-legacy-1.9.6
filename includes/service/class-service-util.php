<?php
/**
 * Service Util
 *
 * @author ConvesioPay - based on the Woosa WC integration
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Service_Util{


   /**
    * Gets the page where the customer will be redirected.
    *
    * @since 1.2.0
    * @param \WC_Order $order
    * @param string $result_code
    * @return string
    */
   public static function get_return_page_url(\WC_Order $order, $result_code){

      $url = in_array(strtolower($result_code), ['pending', 'error', 'cancelled', 'refused']) ? add_query_arg(['payment_result' => 'failed'], $order->get_checkout_payment_url()) : $order->get_checkout_order_received_url();

      return $url;
   }



   /**
    * Retrieves the checkout URL.
    *
    * @param \WC_Order $order
    * @return string
    */
   public static function get_checkout_url($order){
      return Checkout::has_subscription() ? wc_get_checkout_url() : $order->get_checkout_payment_url();
   }



   /**
    * Retrieves the shopper reference based on the WP user id.
    *
    * @since 1.0.6 - generate a unique guest id.
    * @since 1.0.0
    * @return string
    */
    public static function get_shopper_reference(){

      $guest_id = md5(uniqid(time(), true));
      $user_id = is_user_logged_in() ? get_current_user_id() : $guest_id;

      return md5($user_id.'_'.self::get_origin_domain());
   }



   /**
    * Generates origin key for the current shop.
    *
    * @since 1.0.7 - replace home_url with the origin domain
    * @since 1.0.0
    * @param bool $authorized - whether or not the request to be flagged as authorized
    * @return void
    */
    public static function generate_origin_keys($authorized = true){

      $api  = Service::checkout();
      $payload = [
         "originDomains" => [self::get_origin_domain()]
      ];

      $args = [
         'headers' => $api->headers(),
         'body'    => json_encode($payload),
      ];

      if($authorized){
         $args['authorized'] = $api->is_configured();
      }

      $response = Request::POST($args)->send('https://api.convesiopay.com/payment/v1/wc-plugin/origin-keys');

      if($response->status == 200){
         $body = Util::obj_to_arr($response->body);
         Option::set('origin_keys', $body['originKeys']);
      }
   }



   /**
    * Retrieves the origin key for the current domain.
    *
    * @since 1.0.8 - fix: return the value
    * @since 1.0.7 - replace home_url with the origin domain
    * @since 1.0.0
    * @return string|null
    */
   public static function get_origin_key(){

      $key = null;
      $keys = Option::get('origin_keys', []);

      if( ! empty($keys[self::get_origin_domain()]) ){
         $key = $keys[self::get_origin_domain()];
      }

      return $key;
   }



   /**
    * Retrieves the shop domain used for generating origin keys.
    *
    * @since 1.2.0 - check if the server port should included or not.
    * @since 1.0.7
    * @return string
    */
   public static function get_origin_domain(){

      $incl_port = Option::get('incl_server_port', 'yes');
      $protocol  = Util::array($_SERVER)->get('HTTPS') === 'on' ? 'https://' : 'http://';
      $port      = in_array( Util::array($_SERVER)->get('SERVER_PORT'), [ '80', '443' ] ) ? '':':'.Util::array($_SERVER)->get('SERVER_PORT');
      $domain    = 'yes' === $incl_port ? $protocol.Util::array($_SERVER)->get('HTTP_HOST').$port : $protocol.Util::array($_SERVER)->get('HTTP_HOST');

      return $domain;
   }



   /**
    * Gets the number of installments based on the country.
    *
    * @since 1.2.0 - return the installments based on $max_installments value
    * @since 1.0.3
    * @param string $country - ISO CODE
    * @param string $max_installments - the max number of allowed installments
    * @return array|void
    */
   public static function get_installments_by_country($country, $max_installments){

      $max_installments = empty($max_installments) ? 20 : $max_installments;
      $br = [];

      for($i=2; $i<=$max_installments; $i++){
         $br[] = $i;
      }

      $mx = [];
      $mx_allowed = [3, 6, 9, 12, 18];
      $tr = [];
      $tr_allowed = [2, 3, 6, 9];

      foreach($mx_allowed as $mx_val){
         if( $mx_val <= $max_installments){
            $mx[] = $mx_val;
         }
      }

      foreach($tr_allowed as $tr_val){
         if( $tr_val <= $max_installments){
            $tr[] = $tr_val;
         }
      }

      $list = [
         'BR' => $br,
         'MX' => $mx,
         'TR' => $tr,
      ];

      if(isset($list[$country])){
         return $list[$country];
      }

      return '';
   }



   /**
    * Formats a given amount according to required currency decimals.
    *
    * @since 1.0.0
    * @param string $amount
    * @return integer
    */
    public static function format_amount($amount){
      return (int) number_format( $amount, self::currency_decimal(), '', '' );
   }



   /**
    * Retrieves amount decimals for the shop currency code.
    *
    * @since 1.0.0
    * @return integer
    */
   public static function currency_decimal(){

      $three = array('BHD', 'IQD', 'JOD', 'KWD', 'LYD', 'OMR', 'TND');
      $zero = array('CVE', 'DJF', 'GNF', 'IDR', 'JPY', 'KMF', 'KRW', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF');

      if(in_array(get_woocommerce_currency(), $three)) return 3;

      if(in_array(get_woocommerce_currency(), $zero)) return 0;

      return 2;
   }



   /**
    * List of payment methods which are immediately captured.
    *
    * @since 1.1.0
    * @return array
    */
   public static function immediate_payment_methods(){

      return [
         'ideal',
         'giropay',
         'directEbanking',
         'bcmc',
         'alipay'
      ];
   }



   /**
    * List of payment methods which are manually captured.
    *
    * @since 1.2.0 - include Klarna only if the option is disabled
    * @since 1.1.0
    * @return array
    */
   public static function manual_payment_methods(){

      $list = [];

      if( 'yes' !== get_option(PREFIX .'_auto_klarna_payments') ){

         $list = array_merge($list, [
            'klarna',
            'klarna_paynow',
            'klarna_account',
         ]);

      }

      /**
       * Let 3rd-parties to filter the manual payment methods.
       * 
       * @param array $list
       * @since 1.8.0
       * @return array
       */
      return apply_filters(PREFIX . '\service_util\manual_payment_methods\list', $list);
   }



   /**
    * Checks whether the given payment method is manually captured.
    *
    * @since 1.1.0
    * @param string $method
    * @return boolean
    */
   public static function is_manual_payment($method){

      if( in_array($method, self::manual_payment_methods()) ){
         return true;
      }

      return false;
   }



   /**
    * Checks whether the given payment method is immediatley captured.
    *
    * @since 1.1.0
    * @param string $method
    * @return boolean
    */
   public static function is_immediate_payment($method){

      if( in_array($method, self::immediate_payment_methods()) ){
         return true;
      }

      return false;
   }



   /**
    * Checks whether or not a given country code is valid (exists in the WC countries list).
    *
    * @since 1.0.0
    * @param string $code
    * @return boolean
    */
   public static function is_valid_country_code($code){

      $countries = (new \WC_Countries)->get_countries();

      if(array_key_exists(strtoupper($code), $countries)) return true;

      return false;
   }




   /**
    * Checks whether or not the given payment method is activated
    *
    * @since 1.0.4 - add caching for 1 hour
    * @since 1.0.0
    * @param string $method
    * @return boolean
    */
    public static function is_payment_method_active($method){

      $is_active = get_transient( PREFIX . '_is_active_'.$method );

      if( empty($is_active) ){

         foreach(Service::checkout()->get_payment_methods() as $item){
            if(Util::array($item)->get('type') === $method){
               set_transient( PREFIX . '_is_active_'.$method, true, \HOUR_IN_SECONDS );
               return true;
            }
         }
      }

      return $is_active;
   }



   /**
    * Checks whether or not the installment value is valid.
    *
    * @since 1.2.0 - set default $max_installments to 20
    * @since 1.0.3
    * @param string|int $number
    * @param string $country
    * @param string|int $max_installments
    * @param string $are_installments_allowed
    * @return boolean
    */
   public static function is_valid_installment($number, $country, $max_installments, $are_installments_allowed = 'yes'){

      if( $are_installments_allowed !== 'yes' ){
         return false;
      }

      $is_valid = true;
      $max_installments = empty($max_installments) ? 20 : $max_installments;
      $value = self::get_installments_by_country($country, $max_installments);

      if( is_array($value) ){

         if( ! in_array($number, $value) ){
            $is_valid = false;
         }

      }elseif( (int) $value > 0 && $number > $max_installments ){
         $is_valid = false;
      }

      return $is_valid;

   }

}