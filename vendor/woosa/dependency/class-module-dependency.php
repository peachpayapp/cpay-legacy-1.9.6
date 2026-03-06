<?php
/**
 * Module Dependency
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Module_Dependency {


   /**
    * Perform a full check.
    *
    * @param true|array $output
    * @param bool $go_back
    * @return true|array
    */
   public static function is_check_passed($output = true, $go_back = false){

      try {

         self::check_php_version();
         self::check_php_extensions();

         self::check_wp_version();
         self::check_wp_plugins();

         do_action(PREFIX . '\dependency\run_check');

      } catch(\Exception $e){

         $link    = sprintf(__('%sGo back.%s', 'convesiopay-woocommerce'), '<a href="' . admin_url('plugins.php') . '">', '</a>');
         $message = $go_back ? $e->getMessage() . ' ' . $link : $e->getMessage();
         $output  = [
            'error' => $message,
         ];

      }

      return $output;
   }




   /*
   |--------------------------------------------------------------------------
   | PHP
   |--------------------------------------------------------------------------
   */


   /**
    * Max execution time.
    *
    * @return int
    */
   public static function max_exec_time(){
      return apply_filters(PREFIX . '\dependency\max_exec_time', 0);
   }



   /**
    * Whether or not to request a specific execution time value.
    *
    * @return bool
    */
   public static function require_max_exec_time(){

      if(self::max_exec_time() > 0){
         if(ini_get('max_execution_time') < self::max_exec_time()){
            return true;
         }
      }

      return false;
   }



   /**
    * PHP required version.
    *
    * @return string
    */
   public static function php_version(){
      return apply_filters(PREFIX . '\dependency\php_version', '7.4');
   }



   /**
    * PHP required extensions.
    *
    * @return array
    * [
    *    'soap' => 'SoapClient',
    *    'ssh2' => 'ssh2',
    * ]
    */
   public static function php_extensions(){
      return apply_filters(PREFIX . '\dependency\php_extensions', []);
   }



   /**
    * Checks PHP version.
    *
    * @throws \Exception
    * @return void
    */
   protected static function check_php_version(){

      if(version_compare(phpversion(), self::php_version(), '<')){
         throw new \Exception(sprintf(
            __('The server must have at least %s installed.', 'convesiopay-woocommerce'),
            '<b>PHP '.self::php_version().'</b>'
         ));
      }
   }



   /**
    * Checks PHP extensions.
    *
    * @throws \Exception
    * @return void
    */
   protected static function check_php_extensions(){

      $active = get_loaded_extensions();

      foreach(self::php_extensions() as $slug => $name){
         if(!in_array($slug, $active)){
            throw new \Exception(sprintf(
               __('This plugin requires %s extension to be installed on the server.', 'convesiopay-woocommerce'),
               "<b>{$name}</b>"
            ));
         }
      }
   }




   /*
   |--------------------------------------------------------------------------
   | Wordpress
   |--------------------------------------------------------------------------
   */


   /**
    * Wordpress required version.
    *
    * @return string
    */
   public static function wp_version(){
      return apply_filters(PREFIX . '\dependency\wp_version', '5.0');
   }



   /**
    * Wordpress required plugins.
    *
    * @return array
    */
   public static function wp_plugins(){
      return apply_filters(PREFIX . '\dependency\wp_plugins', []);
   }



   /**
    * Checks Wordpress version.
    *
    * @return void|\Exception
    */
   protected static function check_wp_version(){

      if(version_compare(get_bloginfo('version'), self::wp_version(), '<')){
         throw new \Exception(sprintf(
            __('This plugin requires at least %s!', 'convesiopay-woocommerce'),
            '<b>Wordpress '.self::wp_version().'</b>'
         ));
      }
   }



   /**
    * Checks whether the required WP plugins are installed and active.
    *
    * @return void
    */
   protected static function check_wp_plugins(){

      $active = self::get_active_wp_plugins();

      foreach(self::wp_plugins() as $path => $item){

         $message = sprintf(
            __('This plugin requires at least %s to be installed and active.', 'convesiopay-woocommerce'),
            "<b>{$item['name']} {$item['version']}</b>"
         );

         if(in_array($path, $active)){

            if( ! function_exists('get_plugin_data') ){
               require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            }

            $data = get_plugin_data(dirname(DIR_PATH).'/'.$path);

            if(version_compare($data['Version'], $item['version'], '<')){
               throw new \Exception($message);
            }

         }else{

            throw new \Exception($message);
         }

      }
   }



   /**
    * Get active WP plugins
    *
    * @return array
    * */
   public static function get_active_wp_plugins(){

      $active_plugins = apply_filters('active_plugins', get_option('active_plugins'));

      if (is_multisite()) {
         $active_sitewide_plugins = get_site_option('active_sitewide_plugins');

         foreach ($active_sitewide_plugins as $path => $item) {
            $active_plugins[] = $path;
         }
      }

      return $active_plugins;
   }

}