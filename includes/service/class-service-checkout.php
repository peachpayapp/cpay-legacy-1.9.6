<?php
/**
 * Service Checkout
 *
 * @author ConvesioPay - based on the Woosa WC integration
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Service_Checkout extends Service{


   /**
    * Version of the API.
    *
    * @var string
    */
   protected $version = 'v68';



   /**
    * Base of the URL.
    *
    * @param string $endpoint
    * @return string
    */
   public function base_url($endpoint = ''){

      $url = 'https://' . $this->url_prefix . '-checkout-live.'.$this->domain_proxy_2().'/checkout/' . $this->version . '/' . ltrim($endpoint, '/');

      if($this->test_mode){
         $url = 'https://checkout-test.'.$this->domain_proxy_1().'/' . $this->version . '/' . ltrim($endpoint, '/');
      }

      return $url;
   }



   /**
    * Retrieves the entire list of payment methods.
    *
    * @param string $country
    * @param integer $amount
    * @param bool $cached - whether or not to use cached data
    * @return array
    */
   public function list_payment_methods($country = null, int $amount = 0, $cached = true){

      $result = [];

      if($cached){
         $result = get_transient( PREFIX . '_payment_methods_' . $country );
      }

      if ( empty( $result ) ){

         $payload = [
            'merchantAccount'  => $this->merchant,
            'shopperReference' => Service_Util::get_shopper_reference(),
            'channel'          => 'Web',
         ];

         if( ! is_null($country) ){

            $payload = array_merge($payload, [
               'countryCode' => $country,
               'amount'      => [
                  'currency' => get_woocommerce_currency(),
                  'value' => Service_Util::format_amount($amount)
               ]
            ]);
         }

         $response = Request::POST([
            'headers'    => $this->headers(),
            'body'       => json_encode($payload),
            'authorized' => $this->is_configured()
         ])->send('https://api-qa.convesiopay.com/payment/v1/wc-plugin/payment-methods');

         if($response->status == 200){

            $result = Util::obj_to_arr($response->body);

            set_transient( PREFIX . '_payment_methods_' . $country , $result, \DAY_IN_SECONDS );

         }

      }

      return $result;
   }



   /**
    * Retrieves the available payment methods.
    *
    * @param string $country
    * @param integer $amount
    * @return array
    */
   public function get_payment_methods($country = null, int $amount = 0){

      $result   = [];
      $response = $this->list_payment_methods($country, $amount);

      if(isset($response['paymentMethods'])){
         $result = $response['paymentMethods'];
      }

      return $result;
   }



   /**
    * Retrieves the available stored payment methods.
    *
    * @param string $country
    * @param integer $amount
    * @return array
    */
   public function get_stored_payment_methods($country = null, int $amount = 0){

      $reference = Service_Util::get_shopper_reference();
      $result    = array_filter((array) get_transient( PREFIX . '_stored_payment_methods_'.$reference ));

      if(empty($result) && is_user_logged_in()){

         $response = $this->list_payment_methods($country, $amount, false);

         if(isset($response['storedPaymentMethods'])){

            $result = $response['storedPaymentMethods'];

            set_transient( PREFIX . '_stored_payment_methods_'.$reference , $result, \DAY_IN_SECONDS );
         }
      }

      return $result;
   }



   /**
    * Retrieves the stored cards which have `Ecommerce` support.
    *
    * @param string $country
    * @param integer $amount
    * @return array
    */
   public function get_ec_stored_cards($country = null, int $amount = 0){

      $result = [];
      $list   = $this->get_stored_payment_methods($country, $amount);

      foreach($list as $item){
         if(isset($item['lastFour']) && is_array($item['supportedShopperInteractions']) && in_array('Ecommerce', $item['supportedShopperInteractions'])){
            $result[] = $item;
         }
      }

      return $result;
   }



   /**
    * Retrieves the available card types.
    *
    * @param string $country
    * @return array
    */
   public function get_card_types($country = null){

      $result   = [];
      $response = $this->list_payment_methods($country);

      if(isset($response['paymentMethods'])){

         foreach($response['paymentMethods'] as $item){
            if($item['type'] == 'scheme'){
               $result = $item['brands'];
            }
         }
      }

      return $result;
   }



   /**
    * Sends a payment.
    *
    * @param array $payload
    * @return object
    */
   public function send_payment($payload){

      $payload = array_merge($payload, $this->app_info()); //add our app info in the payload

      return Request::POST([
         'headers' => $this->headers(),
         'timeout' => 15,
         'body'    => json_encode($payload)
      ])->send('https://api-qa.convesiopay.com/payment/v1/wc-plugin/payments');

   }



   /**
    * Sends the payment details.
    *
    * @param array $payload
    * @return object
    */
   public function send_payment_details($payload){

      return Request::POST([
         'headers' => $this->headers(),
         'timeout' => 15,
         'body'    => json_encode($payload)
      ])->send('https://api-qa.convesiopay.com/payment/v1/wc-plugin/payment-details');

   }



   /**
    * Refunds a payment.
    *
    * @param string $reference - payment reference to be refunded
    * @param float $amount
    * @return object
    */
   public function refund_payment($reference, $amount){

      $payload = [
         'amount' => [
            'currency' => get_woocommerce_currency(),
            'value'    => Service_Util::format_amount($amount),
         ],
         'merchantAccount' => $this->merchant,
      ];

      return Request::POST([
         'headers' => $this->headers(),
         'body'    => json_encode($payload)
      ])->send('https://api-qa.convesiopay.com/payment/v1/wc-plugin/refunds?reference=' . $reference);

   }



   /**
    * Cancels the payment.
    *
    * @param string $reference - payment reference to be canceled
    * @return object
    */
   public function cancel_payment($reference){

      $payload = [
         'merchantAccount' => $this->merchant
      ];

      return Request::POST([
         'headers' => $this->headers(),
         'body'    => json_encode($payload)
      ])->send('https://api-qa.convesiopay.com/payment/v1/wc-plugin/cancels?reference=' . $reference);

   }



   /**
    * Captures an authorised payment.
    *
    * @param string $reference - payment reference to be captured
    * @param float $amount
    * @return object
    */
   public function capture_payment($reference, $amount){

      $payload = [
         'amount' => [
            'currency' => get_woocommerce_currency(),
            'value'    => Service_Util::format_amount($amount),
         ],
         'merchantAccount' => $this->merchant
      ];

      return Request::POST([
         'headers' => $this->headers(),
         'body'    => json_encode($payload)
      ])->send('https://api-qa.convesiopay.com/payment/v1/wc-plugin/captures?reference=' . $reference);

   }

}