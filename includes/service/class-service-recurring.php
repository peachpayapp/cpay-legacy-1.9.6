<?php
/**
 * Service Recurring
 *
 * @author ConvesioPay - based on the Woosa WC integration
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Service_Recurring extends Service{


   /**
    * Version of the Service.
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

      $url = 'https://pal-'.$this->get_env().'.'.$this->domain_proxy_1().'/pal/servlet/Recurring/' . $this->version . '/' . ltrim($endpoint, '/');

      return $url;
   }



   /**
    * Lists the stored payment details for a shopper, if there are any available.
    *
    * @param string $shopper_reference
    * @return array
    */
   public function list_recurring_details($shopper_reference){

      $result = [];
      $payload = [
         'shopperReference' => $shopper_reference,
         'merchantAccount'  => $this->merchant
      ];

      $response = Request::POST([
         'headers'    => $this->headers(),
         'body'       => json_encode($payload),
         'authorized' => $this->is_configured()
      ])->send('https://api.convesiopay.com/payment/v1/wc-plugin/list-recurring-details');

      if($response->status == 200){

         $result = Util::obj_to_arr($response->body);

      }

      return $result;
   }



   /**
    * Disables stored payment details to stop charging a shopper with this particular recurring detail ID.
    *
    * @param string $shopper_reference
    * @param string $recurr_reference
    * @return object
    */
   public function disable($shopper_reference, $recurr_reference = null){

      $payload = [
         'shopperReference' => $shopper_reference,
         'merchantAccount'  => $this->merchant
      ];

      if( ! is_null($recurr_reference) ){
         $payload['recurringDetailReference'] = $recurr_reference;
      }

      return Request::POST([
         'headers' => $this->headers(),
         'body'    => json_encode($payload)
      ])->send('https://api.convesiopay.com/payment/v1/wc-plugin/disable');

   }

}