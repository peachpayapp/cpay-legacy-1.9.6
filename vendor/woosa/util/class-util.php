<?php
/**
 * Util
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Util{


   /*
   |--------------------------------------------------------------------------
   | CLASS INSTANCES
   |--------------------------------------------------------------------------
   */


   /**
    * Initiates `Util_Array` to extract properties from the given array.
    *
    * @param array $input
    * @return Util_Array
    */
    public static function array($input){
      return new Util_Array($input);
   }



   /**
    * Initiates `Util_Price` to for price formatting.
    *
    * @param string $input
    * @return Util_Price
    */
   public static function price($input){
      return new Util_Price($input);
   }



   /**
    * Initiates `Util_Convert`.
    *
    * @param string $input
    * @return Util_Convert
    */
   public static function convert($input){
      return new Util_Convert($input);
   }



   /**
    * Initiates `Util_File`.
    *
    * @param string $input
    * @return Util_File
    */
   public static function file(){
      return new Util_File();
   }



   /**
    * Initiates `Util_Status`.
    *
    * @param string $input
    * @return Util_Status
    */
   public static function status($input){
      return new Util_Status($input);
   }



   /**
    * Initiates `Util_Alertbox`.
    *
    * @return Util_Alertbox
    */
   public static function alertbox(){
      return new Util_Alertbox();
   }



   /**
    * Initiates `Util_Log`.
    *
    * @return Util_Log
    */
   public static function log(){
      return new Util_Log();
   }




   /*
   |--------------------------------------------------------------------------
   | CONDITIONALS
   |--------------------------------------------------------------------------
   */


   /**
    * Checks if the string is a json.
    *
    * @param string $input
    * @return boolean
    */
   public static function is_json($input){
      return is_string($input) && is_array(json_decode($input, true)) ? true : false;
   }



   /**
    * Checks whether or not it's a valid url format.
    *
    * @param string $url
    * @return boolean
    */
   public static function is_valid_url( $url ) {

      // Must start with http:// or https://.
      if ( 0 !== strpos( $url, 'http://' ) && 0 !== strpos( $url, 'https://' ) ) {
         return false;
      }

      // Must pass validation.
      if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
         return false;
      }

      return true;
   }



   /**
    * Whether or not the allocated memory is exceeded.
    *
    * @return boolean
    */
   public static function is_memory_exceeded(){

      $allocated = self::get_allocated_memory_limit();
      $current   = memory_get_usage(true);

		return $current >= $allocated;

   }



   /**
    * Whether or not the allocated time is exceeded.
    *
    * @param string|float $start_time
    * @return boolean
    */
   public static function is_time_exceeded($start_time){

      $allocated = self::get_allocated_max_execution_time();
      $current   = (microtime(true) - $start_time);

      return $current >= $allocated;
   }



   /**
    * Whether or not the allocated time is valid.
    *
    * @param int $limit
    * @return bool
    */
   public static function is_allocated_max_execution_time_valid($limit){

      $difference_percentage = (($limit - self::get_allocated_max_execution_time(false)) / $limit) * 100;

      // must be at least 50% smaller than the limit
      return $difference_percentage >= 50;
   }




   /*
   |--------------------------------------------------------------------------
   | MISC
   |--------------------------------------------------------------------------
   */


   /**
    * Gets the allocated time for `max_execution_time`.
    *
    * @param bool $valid whether or not to return the valid value.
    * @return float
    */
   public static function get_allocated_max_execution_time($valid = true){

      $seconds = (float) apply_filters(PREFIX . '\util\max_execution_time\allocated_seconds', 35);

      if($valid){

         $limit = (int) ini_get('max_execution_time');

         if( ! self::is_allocated_max_execution_time_valid($limit) ){
            $seconds = floatval($limit * 0.5); //use 50% out of the limit
         }
      }

      return $seconds;
   }



   /**
    * Gets the allocated memory for `memory_limit`.
    *
    * @return float
    */
   public static function get_allocated_memory_limit(){

      $percentage = (float) apply_filters(PREFIX . '\util\memory_limit\allocated_percentage', 0.5);
      $limit      = wp_convert_hr_to_bytes(ini_get('memory_limit'));
      $max_limit  = 2147483648; //2GB

      //set a max limit
		if( $limit < 0 || $limit > $max_limit ) {
			$limit = $max_limit;
		}

      return floatval($limit * $percentage);
   }



   /*
   |--------------------------------------------------------------------------
   | DEPRECATED
   |--------------------------------------------------------------------------
   */


   /**
    * Log errors in Woocommerce logs
    *
    * @param mixed $message
    * @param string $file
    * @param string $line
    * @return void
    */
   public static function wc_error_log($message, $file = '', $line = ''){
      _deprecated_function('Util::wc_error_log', '1.14.0', 'Util::log()->error()');
      self::log()->error($message, $file, $line);
   }



   /**
    * Log debug info in Woocommerce logs
    *
    * @param mixed $message
    * @param string $file
    * @param string $line
    * @return void
    */
   public static function wc_debug_log($message, $file = '', $line = ''){
      _deprecated_function('Util::wc_debug_log', '1.14.0', 'Util::log()->debug()');
      self::log()->debug($message, $file, $line);
   }



   /**
    * Sets a debug log at the start.
    *
    * @param string $method
    * @return void
    */
   public static function debug_log_start(string $method){
      _deprecated_function('Util::debug_log_start', '1.14.0');
   }



   /**
    * Sets a debug log with the data serialied.
    *
    * @param string $method
    * @param mixed $data
    * @return void
    */
   public static function debug_log_report(string $method, $data){
      _deprecated_function('Util::debug_log_report', '1.14.0');
   }



   /**
    * Sets a debug log and the end.
    *
    * @param string $method
    * @param string $data
    * @return void
    */
   public static function debug_log_end(string $method, $data = ''){
      _deprecated_function('Util::debug_log_end', '1.14.0');
   }



   /**
    * Retrieves status in HTML format.
    *
    * @param string $input
    * @return string
    */
   public static function get_status_html($input){
      _deprecated_function('Util::get_status_html', '1.5.0', 'Util::status()->render()');
      return Util::status($input)->render(false, false);
   }



   /**
    * List of available statuses.
    *
    * @return array
    */
   public static function status_list(){

      _deprecated_function('Util::status_list', '1.5.0', 'Util::status()->list()');

      $list = [
         'not_published' => [
            'title' => __('Not published', 'convesiopay-woocommerce'),
            'color' => '',
         ],
         'not_available' => [
            'title' => __('Not available', 'convesiopay-woocommerce'),
            'color' => '',
         ],
         'in_progress' => [
            'title' => __('Processing...', 'convesiopay-woocommerce'),
            'color' => '#18ace6',
         ],
         'processing' => [//this is for bol.com plugin (try to replace it with `in_progress`)
            'title' => __('Processing...', 'convesiopay-woocommerce'),
            'color' => '#18ace6',
            'icon' => 'dashicons dashicons-clock',
         ],
         'open' => [
            'title' => __('Open', 'convesiopay-woocommerce'),
            'color' => '',
         ],
         'created' => [
            'title' => __('Created', 'convesiopay-woocommerce'),
            'color' => '',
         ],
         'processed' => [
            'title' => __('Processed', 'convesiopay-woocommerce'),
            'color' => '#46b450',
         ],
         'registered' => [
            'title' => __('Registered', 'convesiopay-woocommerce'),
            'color' => '#46b450',
         ],
         'published' => [
            'title' => __('Published', 'convesiopay-woocommerce'),
            'color' => '#46b450',
         ],
         'paused' => [
            'title' => __('Paused', 'convesiopay-woocommerce'),
            'color' => '#ffb900',
         ],
         'cancelled' => [
            'title' => __('Cancelled', 'convesiopay-woocommerce'),
            'color' => '',
         ],
         'error' => [
            'title' => __('Error', 'convesiopay-woocommerce'),
            'color' => '#a44',
         ],
      ];
      $list = apply_filters_deprecated(PREFIX . '\util\status_list', [$list], '1.5.0', PREFIX . '\util\status\list');
      $list = apply_filters(PREFIX . '\util\status\list', $list);

      return $list;
   }



   /**
    * Calculates the price with the given additon.
    *
    * @param string $price
    * @param string $addition - can be number: 10 or percentage: 10%
    * @return string
    */
   public static function calculate_price_with_addition($price, $addition){
      _deprecated_function( 'Util::calculate_price_with_addition', '1.3.0', 'Util::price()->addition()' );
      return Util::price($price)->addition($addition);
   }




   /*
   |--------------------------------------------------------------------------
   | MISCELLANEOUS
   |--------------------------------------------------------------------------
   */


   /**
    * Prints give input in a readable format.
    *
    * @param mixed $input
    * @return void
    */
   public static function print($input){
      echo '<pre>'.print_r($input, 1).'</pre>';
   }



   /**
    * Converts an object to an array
    *
    * @since 1.0.0
    * @param object $obj
    * @return array
    */
   public static function obj_to_arr($input){
      return json_decode( json_encode($input), true );
   }



   /**
    * Decodes the JSON string if it's a valid one.
    *
    * @param string $str
    * @return mixed
    */
   public static function maybe_decode_json($input){

      if( self::is_json($input) ){
         return json_decode($input);
      }

      return $input;
   }



   /**
    * Adds plugin prefix to a given string.
    *
    * @param string $input
    * @param bool $dash
    * @return string
    */
    public static function prefix($input, $dash = false){

      if(empty($input)) return $input;

      $separator = $dash ? '-' : '_';

      if(strpos($input, PREFIX . $separator) === false){
         return PREFIX . $separator . $input;
      }

      return $input;
   }



   /**
    * Removes plugin prefix from a given string.
    *
    * @param string $input
    * @param bool $dash
    * @return string
    */
   public static function unprefix($input, $dash = false){

      if(empty($input)) return $input;

      $separator = $dash ? '-' : '_';

      return str_replace(PREFIX . $separator, '', $input);
   }



   /**
    * Checks whether or not the given input has our prefix.
    *
    * @param string $input
    * @return boolean
    */
   public static function has_prefix($input){

      if(empty($input)) return true;

      //dash
      if(strpos($input, PREFIX . '-') === false){
         return false;
      }

      //underline
      if(strpos($input, PREFIX . '_') === false){
         return false;
      }

      return true;
   }



   /**
    * Displays admin network notice.
    *
    * @since 1.0.0
    * @param string $msg
    * @param string $type
    * @return string
    */
    public static function show_network_notice($msg, $type = 'error', $html = false){

      add_action('network_admin_notices', function() use ($msg, $type, $html){
         if($html){
            echo '<div class="wsa-notice notice notice-'.$type.'"><b>'.NAME.':</b> '.$msg.'</div>';
         }else{
            echo '<div class="wsa-notice notice notice-'.$type.'"><p><b>'.NAME.':</b> '.$msg.'</p></div>';
         }
      });
   }



   /**
    * Displays admin notice.
    *
    * @since 1.0.0
    * @param string $msg
    * @param string $type
    * @return string
    */
    public static function show_notice($msg, $type = 'error', $html = false){

      add_action('admin_notices', function() use ($msg, $type, $html){
         if($html){
            echo '<div class="wsa-notice notice notice-'.$type.'"><div style="margin: .5em 0;"><b>'.NAME.':</b> '.$msg.'</div></div>';
         }else{
            echo '<div class="wsa-notice notice notice-'.$type.'"><p><b>'.NAME.':</b> '.$msg.'</p></div>';
         }
      });
   }



   /**
    * Gets content of a given path file.
    *
    * @param string $template_name - the end part of the file path including the template name
    * @param array $args - arguments passed to the template
    * @param string $absolute_path - plugin's DIR_PATH or vendor/woosa path
    * @param mixed $relative_path - relative path added between the absolute path and the template name
    * @return string $content
    */
   public static function get_template($template_name, $args = [], $absolute_path = '', $relative_path = ''){

      extract( apply_filters( PREFIX . '\util\get_template\args', $args) );

      $content = '';

      $template_name = empty( $absolute_path ) && empty( $relative_path ) ? $template_name : trim( $template_name, "/\\" );
      $absolute_path = empty( $absolute_path ) ? '' : trailingslashit( $absolute_path );
      $relative_path = empty( $relative_path ) ? '' : trailingslashit( trim( $relative_path, "/\\" ) );

      $template = $absolute_path . $relative_path . $template_name;

      //check for template in plugin's folder `includes/`
      if(file_exists(DIR_PATH . '/includes/' . $relative_path . $template_name)){
         $template = DIR_PATH . '/includes/' . $relative_path . $template_name;
      }

      $template = apply_filters_deprecated(PREFIX . '\util\get_template\path_file', [ $template, $args ], '1.2.1', PREFIX . '\util\get_template\template');
      $template = apply_filters( PREFIX . '\util\get_template\template', $template, $template_name, $absolute_path, $relative_path);

      if(file_exists($template)){

         ob_start();

         include $template;

         $content = ob_get_clean();
      }

      return $content;
   }



   /**
    * Rturns either block or non for CSS display property.
    *
    * @param mixed $value
    * @param mixed $current
    * @param boolean $echo
    * @return string
    */
   public static function css_display($value, $current, $echo = false){

      $output = $value == $current ? 'display: block;' : 'display: none;';

      if(!$echo){
         return $output;
      }

      echo $output;
   }



   /**
    * Converts a string (e.g. 'yes' or 'no') to bool.
    *
    * @param string|bool $string String to convert. If a bool is passed it will be returned as-is.
    * @return bool
    */
   public static function string_to_bool( $string ) {
      return is_bool( $string ) ? $string : ( 'yes' === strtolower( $string ) || 1 === $string || 'true' === strtolower( $string ) || '1' === $string );
   }



   /**
    * Builds the URL based on the given parts.
    *
    * @return string
    */
   public static function build_url($parts = []){

      $new_url = empty($parts['scheme']) ? 'https://' : $parts['scheme'] . '://';

      if (isset($parts['user'])) {
         $new_url .= $parts['user'];
         if (isset($parts['pass'])) {
               $new_url .= ':' . $parts['pass'];
         }
         $new_url .= '@';
      }

      $new_url .= empty($parts['host']) ? '' : $parts['host'];
      $new_url .= empty($parts['port']) ? '' : ':' . $parts['port'];
      $new_url .= empty($parts['path']) ? '' : $parts['path'];

      if (isset($parts['query'])) {
         $new_url .= is_array($parts['query']) ? '?' . http_build_query($parts['query']) : '?' . $parts['query'];
      }

      $new_url .= empty($parts['fragment']) ? '' : '#' . $parts['fragment'];

      return $new_url;
   }



   /**
    * Generates a random string.
    *
    * @param integer $length
    * @return string
    */
   public static function random_string( $length = 10 ) {

      $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
      $charactersLength = strlen( $characters );
      $randomString = '';

      for ( $i = 0; $i < $length; $i++ ) {
         $randomString .= $characters[ rand( 0, $charactersLength - 1 ) ];
      }
      return $randomString;

   }



   /**
    * Registers and enqueues the given JS/CSS files.
    *
    * @param array $items
    * @return void
    */
   public static function enqueue_scripts(array $items){

      foreach($items as $item){

         // CSS
         if(isset($item['css'])){

            if(isset($item['css']['deps_css'])){
               _deprecated_function('The array property css/deps_css', 'Core:1.0.6', 'css/dependency');
            }

            $name       = Util::array($item)->get('css/name', Util::array($item)->get('name'));
            $handle     = Util::array($item)->get('css/handle', Util::prefix($name, true));
            $register   = Util::array($item)->get('css/register', Util::array($item)->get('register', true));
            $path       = untrailingslashit(Util::array($item)->get('css/path', Util::array($item)->get('path', true)));
            $enqueue    = Util::array($item)->get('css/enqueue', Util::array($item)->get('enqueue', true));
            $dependency = Util::array($item)->get('css/dependency', Util::array($item)->get('css/deps_css', []));
            $version    = Util::array($item)->get('css/version', VERSION);

            if($handle){

               if($register && $path && $name){

                  wp_register_style(
                     $handle,
                     $path . "/{$name}.css",
                     $dependency,
                     $version
                  );
               }

               if($enqueue){
                  wp_enqueue_style($handle);
               }
            }
         }


         // JS
         if(isset($item['js'])){

            if(isset($item['js']['deps_js'])){
               _deprecated_function('The array property js/deps_js', 'Core:1.0.6', 'js/dependency');
            }

            $name       = Util::array($item)->get('js/name', Util::array($item)->get('name'));
            $handle     = Util::array($item)->get('js/handle', Util::prefix($name, true));
            $register   = Util::array($item)->get('js/register', Util::array($item)->get('register', true));
            $path       = untrailingslashit(Util::array($item)->get('js/path', Util::array($item)->get('path', true)));
            $enqueue    = Util::array($item)->get('js/enqueue', Util::array($item)->get('enqueue', true));
            $dependency = Util::array($item)->get('js/dependency', Util::array($item)->get('js/deps_js', []));
            $version    = Util::array($item)->get('js/version', VERSION);

            if($handle){

               if($register && $path && $name){

                  wp_register_script(
                     $handle,
                     $path . "/{$name}.js",
                     $dependency,
                     $version,
                     true
                  );
               }

               if($enqueue){

                  if(isset($item['js']['localize'])){

                     $args = [
                        'ajax' => array(
                           'url' => admin_url( 'admin-ajax.php' ),
                           'nonce' => wp_create_nonce( 'wsa-nonce' )
                        ),
                        'prefix' => PREFIX,
                        'translation' => [
                           'processing' => __('processing...', 'convesiopay-woocommerce'),
                           'saving' => __('saving...', 'convesiopay-woocommerce'),
                        ],
                     ];

                     if(is_array($item['js']['localize'])){
                        $args = array_merge_recursive($args, $item['js']['localize']);
                     }

                     wp_localize_script(
                        $handle,
                        PREFIX . '_' . str_replace('-', '_', $name),//js object name
                        $args
                     );
                  }

                  wp_enqueue_script($handle);
               }
            }
         }
      }
   }



   /**
    * Retrieves the path to the upload folder.
    *
    * @param string $filename
    * @param string $dir
    * @return string
    */
   public static function get_upload_path($filename = '', $dir = ''){

      $upload  = wp_upload_dir();
      $basedir = $upload['basedir'];
      $uploads = $basedir."/".DIR_NAME."_uploads";

      if( !is_dir($uploads)) {
         mkdir($uploads);
      }

      if( !file_exists("{$uploads}/index.html")){
         file_put_contents("{$uploads}/index.html", "");
      }

      //check sub-dir
      if( !is_dir("{$uploads}/{$dir}")) {
         mkdir("{$uploads}/{$dir}", 0777, true);
      }
      if( !file_exists("{$uploads}/{$dir}/index.html")){
         file_put_contents("{$uploads}/{$dir}/index.html", "");
      }

      if(empty($dir)){
         return "$uploads/{$filename}";
      }

      return "$uploads/{$dir}/{$filename}";
   }



   /**
    * Retrieves the URL of the upload folder.
    *
    * @param string $filename
    * @return string
    */
   public static function get_upload_url($filename = ''){

      $upload  = wp_upload_dir();
      $baseurl = $upload['baseurl'];
      $uploads = $baseurl."/".DIR_NAME."_uploads";

      return $uploads . '/'. trim($filename, '/');
   }



   /**
    * Retrieves only our prefixed meta data for the given post id.
    *
    * @param int|string $id
    * @param string $meta_type
    * @param string $output - `key-value` | `all`
    * @return array
    */
   public static function get_prefixed_meta_data($id, $meta_type = 'post', $output = 'key-value'){

      global $wpdb;

      $db_name = $wpdb->postmeta;
      $results = [];

      switch($meta_type){
         case 'term': $db_name = $wpdb->termmeta; break;
         case 'user': $db_name = $wpdb->usermeta; break;
         case 'comment': $db_name = $wpdb->commentmeta; break;
      }

      $sql   = sprintf("SELECT * FROM %s WHERE meta_key LIKE '%s' AND post_id = '%s'", $db_name, PREFIX . '_%', $id);
      $query = $wpdb->get_results($sql, 'ARRAY_A');

      foreach($query as $item){

         if('key-value' === $output){
            $results[$item['meta_key']] = $item['meta_value'];
         }else{
            $results[] = [
               'id'    => $item['meta_id'],
               'key'   => $item['meta_key'],
               'value' => $item['meta_value'],
            ];
         }
      }

      return $results;
   }



   /**
    * Remove the query params from url
    *
    * @param $url
    * @return string
    */
   public static function strip_url($url) {

      $clean_url = strtok(esc_url($url), '?');

      if (!$clean_url) {
         return esc_url($url);
      }

      return $clean_url;

   }



   /**
    * Removes backslashes.
    *
    * @param string $input
    * @return string
    */
   public static function remove_backslashes($input){

      $result = '';

      if( ! empty($input) && ! is_array($input) && ! is_object($input)){

         $input = (string) $input;
         $input = implode("", explode("\\", $input) );

         $result = stripslashes(trim($input));
      }

      return (string) $result;
   }


}
