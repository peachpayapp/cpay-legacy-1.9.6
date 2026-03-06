<?php
/**
 * Service
 *
 * @author ConvesioPay - based on the Woosa WC integration
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Service{


   CONST PROXY_AVAILABILITY_TRANSIENT_NAME = 'proxy_availability';

   /**
    * API key.
    *
    * @var string
    */
   protected $api_key = '';


   /**
    * Merchant account name.
    *
    * @var string
    */
   protected $merchant = '';


   /**
    * Whether or not to user test mode.
    *
    * @var bool
    */
   protected $test_mode = false;


   /**
    * The URL prefifx for LIVE environment.
    *
    * @var string
    */
   protected $url_prefix = '';



   /**
    * Construct of the class.
    */
   public function __construct(){

      $this->test_mode  = Util::string_to_bool(Option::get('testmode', 'no'));
      $this->api_key    = $this->test_mode ? Option::get('test_api_key') : Option::get('api_key');
      $this->merchant   = $this->test_mode ? Option::get('test_merchant_account') : Option::get('merchant_account');
      $this->url_prefix = $this->test_mode ? '' : Option::get('url_prefix');
   }



   /**
    * Retrieves the merchant account.
    *
    * @return string
    */
   public function get_merchant(){
      return $this->merchant;
   }



   /**
    * Retrieves the current environment.
    *
    * @return string
    */
   public function get_env(){
      return $this->test_mode ? 'test' : 'live';
   }



   /**
    * Checks whether or not is the test mode active.
    *
    * @return bool
    */
   public function is_test_mode(){
      return $this->test_mode;
   }



   /**
    * Checks whether or not the current env is configured.
    *
    * @since 1.0.1
    * @return boolean
    */
   public function is_configured(){

      $ma = new Module_Authorization;

      if( ! $ma->is_authorized() ){
         return false;
      }

      return true;
   }



   /**
    * List of headers.
    *
    * @return array
    */
   public function headers(){

      return [
         'Content-Type'                => 'application/json',
         'X-API-Key'                   => $this->api_key,
         'x-convesiopay-api-key'       => Option::get('adn_convesiopay_api_key', ''),
         'x-woosa-domain'              => parse_url(home_url(), PHP_URL_HOST),
         'x-woosa-license'             => Option::get('license_key', ''),
         'x-woosa-plugin-slug'         => DIR_NAME,
         'x-woosa-plugin-version'      => VERSION,
         'x-wc-convesiopay-merchant-name' => $this->merchant,
      ];
   }



   /**
    * Returns the information about our plugin and the CMS.
    *
    * @return array
    */
   public function app_info(){

      return [
         'applicationInfo' => [
            'merchantApplication' => [
               'name' => NAME,
               'version' => VERSION
            ],
            'externalPlatform' => [
               'name' => 'WooCommerce',
               'version' => get_bloginfo('version'),
               'integrator' => 'Woosa'
            ],
         ]
      ];
   }



   /**
    * Instance of Checkout service.
    *
    * @return Service_Checkout
    */
   public static function checkout(){
      return new Service_Checkout();
   }



   /**
    * Instance of Recurring service.
    *
    * @return Service_Recurring
    */
   public static function recurring(){
      return new Service_Recurring();
   }



   /**
    * Instance of Management service
    *
    * @return Service_Management
    */
   public static function management() {
      return new Service_Management();
   }



   /**
    * Removes the personal data stored within the payment.
    *
    * @param string $reference -  payment reference
    * @return object
    */
   public function remove_personal_data($reference){

      $url = 'https://ca-'.$this->get_env().'.'.$this->domain_proxy_1().'/ca/services/DataProtectionService/v1/requestSubjectErasure';

      $response = Request::POST([
         'headers' => $this->headers(),
         'body'    => json_encode([
            'merchantAccount' => $this->merchant,
            'pspReference' => $reference,
            'forceErasure' => true,
         ])
      ])->send($url);

      return $response;
   }



   /**
    * The domain proxy one.
    *
    * @return string
    */
   public function domain_proxy_1(){

      if (!$this->is_proxy_available()) {

         return $this->get_domain();

      }

      return 'adyen.com';
   }



   /**
    * The domain proxy two.
    *
    * @return string
    */
   public function domain_proxy_2(){

      if (!$this->is_proxy_available()) {

         return $this->get_domain_2();

      }

      return 'adyenpayments.com';
   }



   /**
    * Get original Adyen domain
    *
    * @return string
    */
   public function get_domain() {
      return 'adyen.com';
   }



   /**
    * Get second original Adyen domain
    *
    * @return string
    */
   public function get_domain_2() {
      return 'adyenpayments.com';
   }



   /**
    * Check if the proxy is available
    *
    * @return bool
    */
   public function is_proxy_available() {

      $is_proxy_available = Transient::get(self::PROXY_AVAILABILITY_TRANSIENT_NAME);

      if (!empty($is_proxy_available) && 'no' === $is_proxy_available) {
         return false;
      }

      return true;

   }

}
