<?php
/**
 * Module License
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Module_License {


   /**
    * Package.
    *
    * @var string
    */
   public $package;


   /**
    * Site URL.
    *
    * @var string
    */
   public $site_url;


   /**
    * Licence key.
    *
    * @var string
    */
   public $key;



   /**
    * Use it for being added in the list of declared classes.
    * This has no functionality.
    *
    * @return void
    */
   public static function declare(){}



   /**
    * Construct of this class.
    *
    */
   public function __construct($key = '', $site_url = '', $package = ''){

      $this->key      = '' === $key ? Option::get('license_key', '') : $key;
      $this->site_url = '' === $site_url ? get_bloginfo('url') : $site_url;
      $this->package  = '' === $package ? DIR_NAME : $package;

   }




   /*
   |--------------------------------------------------------------------------
   | CONDITIONALS
   |--------------------------------------------------------------------------
   */


   /**
    * Checks whether or not the automatic updates is active
    *
    * @return boolean $active
    */
   public function is_active(){

      $active = false;

      if ( 'active' === Option::get('license_status') ) {
         $active = true;
      }

      return apply_filters( PREFIX . '\license\is_active', $active, $this );
   }




   /*
   |--------------------------------------------------------------------------
   | GETTERS
   |--------------------------------------------------------------------------
   */


   /**
    * List of request headers.
    *
    * @return array
    */
   public function headers(){

      return [
         'content-type' => 'application/json'
      ];
   }


   /**
    * Retreives the server URL of the license supplier.
    *
    * @return string
    */
   public function get_supplier_url(){

      $result = '';

      if ( ! empty( $this->key ) && ( strlen( $this->key ) % 2 == 0 ) && ctype_xdigit( $this->key ) ) {

         $iv      = 'ab86d144ab86d144';
         $cipher  = "aes-128-ctr";
         $decrypt = openssl_decrypt( hex2bin( $this->key ), $cipher, '', OPENSSL_RAW_DATA, $iv);
         $data    = explode( '*', $decrypt );
         $result  = $data[0] ?? '';
      }

      return $result;
   }



   /**
    * Gets the full API url for a given endpoint.
    *
    * @param string $endpoint
    * @return string
    */
   public function get_api_url($endpoint){

      $result = '';
      $base_url = $this->get_supplier_url();

      if($base_url){
         $result = "https://{$base_url}/wp-json/lmn/v1/".ltrim($endpoint, '/');
      }

      return $result;
   }




   /*
   |--------------------------------------------------------------------------
   | SETTERS
   |--------------------------------------------------------------------------
   */


   /**
    * Sets the status as active.
    *
    * @param boolean $deactivate
    * @return void
    */
   public function set_active() {

      Option::set('license_status', 'active');

      //let other plugins to extend
      do_action(PREFIX . '\license\set_active', $this);
   }



   /**
    * Sets the status as inactive.
    *
    * @param boolean $deactivate
    * @return void
    */
   public function set_inactive() {

      Option::delete('license_status');

      $this->cache_info('');
      $this->cache_update('');

      //let other plugins to extend
      do_action(PREFIX . '\license\set_inactive', $this);
   }



   /**
    * Saves locally the update received from the supplier.
    *
    * @param mixed $data
    * @return void
    */
   public function cache_update($data) {

      if(empty($data)){
         Option::delete('plugin_update');
      }else{
         Option::set('plugin_update', json_decode(json_encode($data)));
      }
   }



   /**
    * Saves locally the license info.
    *
    * @param mixed $data
    * @return void
    */
   public function cache_info($data){

      if(empty($data)){
         Option::delete('license_info');
      }else{
         Option::set('license_info', json_decode(json_encode($data)));
      }
   }




   /*
   |--------------------------------------------------------------------------
   | LICENSE API
   |--------------------------------------------------------------------------
   */


   /**
    * Retrieves the license information.
    *
    * @param bool $no_cache
    * @return object
    */
   public function get_info($no_cache = false){

      $result = Option::get('license_info');

      if(empty($result) || $no_cache){

         $response = Request::GET([
            'query_params' => [
               'key'     => $this->key,
               'package' => $this->package,
            ]
         ])->send( $this->get_api_url('license/info') );

         $result = $response->body;

         $this->cache_info($result);
      }

      return $result;
   }



   /**
    * Activates the license.
    *
    * @return object
    */
   public function activate() {

      $response = Request::POST([
         'headers' => $this->headers(),
         'body'    => json_encode([
            'key'      => $this->key,
            'package'  => $this->package,
            'site_url' => $this->site_url,
         ])
      ])->send( $this->get_api_url('license/activate') );

      $result = $response->body;

      if($response->status == 200){

         $this->cache_info($result);

         //let other plugins to extend
         do_action(PREFIX . '\license\activated', $result, $this);
      }

      return $result;
   }



   /**
    * Deactivates the license.
    *
    * @return object
    */
   public function deactivate() {

      $response = Request::POST([
         'headers' => $this->headers(),
         'body'    => json_encode([
            'key'      => $this->key,
            'package'  => $this->package,
            'site_url' => $this->site_url,
         ])
      ])->send( $this->get_api_url('license/deactivate') );

      $result = $response->body;

      if($response->status == 200){

         $this->cache_info($result);

         //let other plugins to extend
         do_action(PREFIX . '\license\deactivated', $result, $this);
      }

      return $result;
   }



   /**
    * Retrieves the software information.
    *
    * @param bool $no_cache
    * @return object
    */
   public function get_software_info($no_cache = false){

      $result = Option::get('plugin_update');

      if( empty($result) || $no_cache ){

         $response = Request::GET([
            'query_params' => [
               'key'      => $this->key,
               'package'  => $this->package,
               'site_url' => $this->site_url,
            ]
         ])->send( $this->get_api_url('software/info') );

         $result = $response->body;

         $this->cache_update($result);
      }

      return $result;
   }




   /*
   |--------------------------------------------------------------------------
   | MISC
   |--------------------------------------------------------------------------
   */


   /**
    * Displays the template for activate/deactive licenses.
    *
    * @param array $values
    * @return string
    */
   public static function render($values){

      $license = new self;

      if ( $license->is_active() ) {
         $svg = '<span class="icon-small">' .
            file_get_contents(untrailingslashit(plugin_dir_path(__FILE__)) . '/assets/images/icons/circle-check.svg')
         . '</span>';
         $status = '<span style="color: #68BD6D;fill: #68BD6D;"> ' . $svg . ' ' . __('Active', 'convesiopay-woocommerce').'</span>';
      } else {
         $svg = '<span class="icon-small">' .
            file_get_contents(untrailingslashit(plugin_dir_path(__FILE__)) . '/assets/images/icons/circle-xmark.svg')
         . '</span>';
         $status = '<span style="color: #B00;fill: #B00;"> ' . $svg . ' ' . __('Inactive', 'convesiopay-woocommerce').'</span>';
      }

      $info = $license->get_info();

      $activations = isset($info->license) ? $info->license->activations : '';
      $activation_limit = isset($info->license) ? $info->license->activation_limit : '';
      $activation_limit = $activation_limit < 1 ? '&infin;' : $activation_limit;
      $activaion_stats = '' === $activations || '' === $activation_limit ? '-' : $activations.'/'.$activation_limit;

      echo Util::get_template('license-ui.php', [
         'status'          => $status,
         'activaion_stats' => $activaion_stats,
         'license'         => $license,
         'values'          => $values,
      ], dirname(dirname(__FILE__)), 'license/templates');
   }

}
