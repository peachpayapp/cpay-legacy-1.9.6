<?php
/**
 * Request
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;

use stdClass;

//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Request {


   /**
    * A hash used for locking the request.
    *
    * @var string
    */
   protected $hash = '';


   /**
    * URL where to send the request.
    *
    * @var string
    */
   protected $url;


   /**
    * Request arguments.
    *
    * @var array
    */
   protected $args = [];


   /**
    * Request response.
    *
    * @var object
    */
   protected $response;


   /**
    * Request log.
    *
    * @var array
    */
   protected $log = [];


   /**
    * Request ETag.
    *
    * @var string
    */
   protected $etag = '';



   /**
    * Sends the request to the given URL.
    *
    * @param string $url
    * @return object
    */
   public function send($url){

      $this->set_url($url);
      $this->set_hash();
      $this->set_args( apply_filters(PREFIX . '\request\args', $this->args, $this->url, $this) );

      $this->maybe_use_signature();
      $this->maybe_use_etag();

      if( Util::array($this->args)->get('authorized', true) ){

         $cached = $this->get_cached_body();

         if( ! empty($cached) ){

            $result = (object) [
               'info'   => 'THIS IS A CACHED RESPONSE!',
               'status' => 200,
               'body'   => $cached,
            ];

         }elseif( $this->is_locked() ){

            $result = (object)[
               'status' => 102,
               'body' => [
                  'service' => 'wp_plugin',
                  'message' => 'A similar request is already processing. This one will be skipped.'
               ]
            ];

         }else{

            $dummy_response = apply_filters(PREFIX . '\request\send\dummy_response', false, $this);

            if( $dummy_response ){

               $result = $dummy_response;

            }else{

               $this->lock();

               $response = wp_remote_request( $this->url, $this->args );

               $this->unlock();

               if( is_wp_error($response) ){

                  $code = $response->get_error_code();
                  $body = [
                     'service' => 'wp_plugin',
                     'message' => $response->get_error_message()
                  ];

                  $result = (object)[
                     'status' => 417,
                     'code'   => $code,
                     'body'   => $body
                  ];

               } else {

                  $headers = wp_remote_retrieve_headers($response)->getAll();
                  $code    = wp_remote_retrieve_response_code($response);
                  $body    = Util::maybe_decode_json(wp_remote_retrieve_body($response));

                  $this->set_etag(Util::array($headers)->get('etag', '', false));

                  $result = (object) [
                     'headers' => $headers,
                     'status'  => $code,
                     'body'    => $body,
                  ];
               }
            }

         }

      }else{

         $result = (object)[
            'status' => 401,
            'body' => [
               'service' => 'wp_plugin',
               'message' => 'The plugin is not authorized to perform this request.'
            ]
         ];

      }

      do_action(PREFIX . '\request\sent', $result, $this);

      $this->set_response($result);

      $this->maybe_set_logs($result);
      $this->maybe_cache_body($result->body, $result->status);

      return $result;

   }




   /*
   |--------------------------------------------------------------------------
   | SETTERS
   |--------------------------------------------------------------------------
   */


   /**
    * Sets the request hash.
    *
    * @return void
    */
   public function set_hash(){
      $this->hash = hash('md5', $this->url);
   }


   /**
    * Sets the URL where the request will be sent. In case there are query params will to be added.
    *
    * @param string $url
    * @return string
    */
   public function set_url(string $url){

      if( ! empty($this->args['query_params']) ){

         $url_data = parse_url($url);
         $url_data['query'] = empty($url_data['query']) ? $this->args['query_params'] : $url_data['query'] . http_build_query($this->args['query_params']);

         $url = Util::build_url($url_data);
      }

      $this->url = apply_filters(PREFIX . '\request\url', $url, $this->args );
   }



   /**
    * Sets the list of arguments.
    *
    * @param array $args
    * @return void
    */
   public function set_args(array $args){

      $this->args = array_merge($this->args, $args);

      if(empty($this->args['timeout'])){
         $this->args['timeout'] = 5;
      }

      if (empty($this->args['headers']['x-woosa-domain'])) {
         $this->args['headers']['x-woosa-domain'] = strtolower(parse_url(home_url(), PHP_URL_HOST));
      }

      if (empty($this->args['headers']['x-woosa-license'])) {
         $this->args['headers']['x-woosa-license'] = Option::get('license_key', '');
      }

      if (empty($this->args['headers']['x-woosa-plugin-version'])) {
         $this->args['headers']['x-woosa-plugin-version'] = VERSION;
      }

      if (empty($this->args['headers']['x-woosa-plugin-slug'])) {
         $this->args['headers']['x-woosa-plugin-slug'] = DIR_NAME;
      }

      if (empty($this->args['headers']['x-woosa-marketplace-name'])) {
         $this->args['headers']['x-woosa-marketplace-name'] = $this->get_service();
      }
   }



   /**
    * Sets the request response.
    *
    * @param object $response
    * @return void
    */
   public function set_response(object $response){
      $this->response = $response;
   }



   /**
    * Sets the ETag.
    *
    * @param string $etag
    * @return string
    */
   public function set_etag(string $etag){

      if( ! empty($etag) ){

         $list = Option::get('request:etags', []);

         if(count($list) == 5000){
            array_pop($list); //remove the last one
         }

         $this->etag = $etag;

         $list = [$this->hash => $etag] + $list;//add the new value at the beginning of the list

         Option::set('request:etags', $list);
      }
   }




   /*
   |--------------------------------------------------------------------------
   | GETTERS
   |--------------------------------------------------------------------------
   */


   /**
    * Retrieves the request hash.
    *
    * @return string
    */
   public function get_hash(){
      return $this->hash;
   }


   /**
    * Retrieves the URL where the request is sent.
    *
    * @return string
    */
   public function get_url(){
      return $this->url;
   }



   /**
    * Retrieves the list of arguments.
    *
    * @return array
    */
   public function get_args(){
      return $this->args;
   }



   /**
    * Retrieves the response.
    *
    * @return object
    */
   public function get_response(){
      return $this->response;
   }



   /**
    * Retrieves the log.
    *
    * @return array
    */
   public function get_log(){
      return $this->log;
   }



   /**
    * Retrieves the ETag value.
    *
    * @return string
    */
   public function get_etag(){

      if(empty($this->etag)){
         $this->etag = Util::array( Option::get('request:etags', []) )->get($this->hash, '', false);
      }

      return $this->etag;
   }



   /**
    * Get the service
    *
    * @return string
    */
   private function get_service() {
      return 'adyen';
   }




   /*
   |--------------------------------------------------------------------------
   | CONDITIONALS
   |--------------------------------------------------------------------------
   */


   /**
    * Checks whether or not the request is locked.
    *
    * @return boolean
    */
   protected function is_locked(){
      return Transient::get("request:locked:{$this->hash}");
   }




   /*
   |--------------------------------------------------------------------------
   | SUPPORTED METHODS
   |--------------------------------------------------------------------------
   */


   /**
    * Sets the request method as `HEAD`.
    *
    * @param array $args
    * @return Request
    */
   public static function HEAD(array $args = []){

      $args['method'] = 'HEAD';

      $instance = new self();
      $instance->set_args($args);

      return $instance;
   }



   /**
    * Sets the request method as `POST`.
    *
    * @param array $args
    * @return Request
    */
    public static function POST(array $args = []){

      $args['method'] = 'POST';

      $instance = new self();
      $instance->set_args($args);

      return $instance;
   }



   /**
    * Sets the request method as `PUT`.
    *
    * @param array $args
    * @return Request
    */
    public static function PUT(array $args = []){

      $args['method'] = 'PUT';

      $instance = new self();
      $instance->set_args($args);

      return $instance;
   }



   /**
    * Sets the request method as `PATCH`.
    *
    * @param array $args
    * @return Request
    */
    public static function PATCH(array $args = []){

      $args['method'] = 'PATCH';

      $instance = new self();
      $instance->set_args($args);

      return $instance;
   }



   /**
    * Sets the request method as `GET`.
    *
    * @param array $args
    * @return Request
    */
   public static function GET(array $args = []){

      $args['method'] = 'GET';

      $instance = new self();
      $instance->set_args($args);

      return $instance;
   }



   /**
    * Sets the request method as `DELETE`.
    *
    * @param array $args
    * @return Request
    */
   public static function DELETE(array $args = []){

      $args['method'] = 'DELETE';

      $instance = new self();
      $instance->set_args($args);

      return $instance;
   }




   /*
   |--------------------------------------------------------------------------
   | MISC
   |--------------------------------------------------------------------------
   */


   /**
    * Sets the flag to mark the request as locked.
    *
    * @return void
    */
    protected function lock(){
      Transient::set("request:locked:{$this->hash}", true, \MINUTE_IN_SECONDS);
   }



   /**
    * Removes the flag to unlock the request.
    *
    * @return void
    */
   protected function unlock(){
      Transient::delete("request:locked:{$this->hash}");
   }



   /**
    * Sets the body in a transient.
    *
    * @param mixed $body
    * @param int $code
    * @return void
    */
   protected function maybe_cache_body($body, $code){

      if( 'GET' === $this->args['method'] && Util::array($this->args)->get('cache', true) == true && in_array($code, [200, 201])){
         Transient::set("request:cached:{$this->hash}", $body, \DAY_IN_SECONDS);
      }
   }



   /**
    * Retrieves the cached body.
    *
    * @return mixed
    */
   protected function get_cached_body(){
      return 'GET' === $this->args['method'] && Util::array($this->args)->get('cache', true) == true ? Transient::get("request:cached:{$this->hash}") : '';
   }



   /**
    * Sets ETag header if it's required and not empty.
    *
    * @return void
    */
   public function maybe_use_etag(){

      if( ! empty($this->args['use_etag']) ){

         $etag = $this->get_etag();

         if( ! empty($etag) ){
            $this->args['headers'] = array_merge( Util::array($this->args)->get('headers', []), [
               'if-none-match' => $etag
            ]);
         }
      }

   }



   /**
    * Sets signature header if it's required.
    *
    * @return void
    */
   public function maybe_use_signature(){

      if( ! empty($this->args['use_signature']) ){

         $this->args['headers'] = array_merge( Util::array($this->args)->get('headers', []), [
            'x-woosa-signature' => self::generate_signature([
               'path'  => Util::array(parse_url($this->url))->get('path'),
               'query' => Util::array(parse_url($this->url))->get('query'),
               'body'  => Util::array($this->args)->get('body', '', false),
            ])
         ]);
      }

   }



   /**
    * Sets logs with the given response.
    *
    * @param object $object
    * @return void
    */
   protected function maybe_set_logs($object){

      // Not set logs if the cached response
      $cached = $this->get_cached_body();

      if( ! empty($cached) ){
         return;
      }

      $response = new \stdClass;

      foreach($object as $key => $value){
         if('body' === $key){
            $response->$key = json_encode($value);
         }else{
            $response->$key = $value;
         }
      }

      $data = [
         'title' => '==== PERFORM REMOTE REQUEST ====',
         'message' => 'This is a performed remote request.',
         'data' => [
            'request' => array_merge([
               'endpoint' => $this->url,
            ], $this->args),
            'response' => $response
         ]
      ];

      if(isset($this->args['body'])){
         $this->args['body'] = Util::maybe_decode_json($this->args['body']);
      }

      $this->log = [
         'request' => array_merge([
            'endpoint' => $this->url,
         ], $this->args),
         'response' => $object
      ];

      if( $response->status >= 400 ){
         Util::log()->error($data, __FILE__, __LINE__ );
      }

      if(DEBUG){
         Util::log()->debug($data, __FILE__, __LINE__ );
      }
   }



   /**
    * Generates a signature.
    *
    * @param array $args
    * @return string
    */
   public static function generate_signature($args){

      $time   = (int) Util::array($args)->get('time', time());
      $secret = Util::array($args)->get('secret', Option::get('woosa_secret', '', false));
      $path   = Util::array($args)->get('path');
      $query  = Util::array($args)->get('query');

      $data = [
         'uri'  => empty($query) ? $path : $path . '?' . $query,
         'body' => Util::array($args)->get('body', '', false),
         'time' => $time
      ];

      $encoded = base64_encode(hash_hmac('sha256', json_encode($data), $secret, true));
      $result  = sprintf('%s.%s', $time, $encoded); //e.g. 1636117273.MNw1Rd5O0evUmwXy85j0ca2bg8SDg/Xm4WfA3LdI5gg=

      return $result;
   }


}
