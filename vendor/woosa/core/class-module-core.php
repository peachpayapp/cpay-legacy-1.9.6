<?php
/**
 * Module Core
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Module_Core{


   /**
    * Initiates default modules.
    *
    * @return void
    */
   public static function init_modules(){

      $path_1 = glob(DIR_PATH . '/vendor/woosa/**/index.php');
      $path_2 = glob(DIR_PATH . '/vendor/woosa/**/**/index.php');
      $path_3 = glob(DIR_PATH . '/vendor/woosa/**/**/**/index.php');
      $path_4 = glob(DIR_PATH . '/includes/**/index.php');
      $path_5 = glob(DIR_PATH . '/includes/**/**/index.php');
      $path_6 = glob(DIR_PATH . '/includes/**/**/**/index.php');
      $paths  = array_merge($path_1, $path_2, $path_3, $path_4, $path_5, $path_6);

		if( is_array($paths) && count($paths) > 0 ){
			foreach( $paths as $file ) {
				if ( file_exists( $file ) ) {
					include_once $file;
				}
			}
		}
   }



   /**
    * Initiates special classes. This should be used only before the rest of the modules.
    *
    * @return void|\Exception
    */
   public static function pre_run(){

      $dep_classes = [
         __NAMESPACE__ . '\Dependency_Hook', //plugin includes folder
         __NAMESPACE__ . '\Module_Marketplace_Dependency_Hook', //Marketplace module
         __NAMESPACE__ . '\Module_Dropshipping_Dependency_Hook', //Dropshipping module
         __NAMESPACE__ . '\Module_Dependency_Hook', //Depenedncy module
      ];

      foreach($dep_classes as $class){
         if(class_exists($class)){
            $class::init();
         }
      }

      //check whether or not the plugin has a `Core_Hook` class
      if(class_exists(__NAMESPACE__ . '\Core_Hook')){
         Core_Hook::init();
      }
   }



   /**
    * Sets an instance of the website.
    *
    * @param array $data
    * @return array
    */
   public static function set_instance($data = []){

      //make sure all filters are removed
      remove_all_filters( 'home_url' );

      $instance = array_merge([
         'url'     => untrailingslashit(home_url('/', 'https')),
         'domain'  => parse_url(home_url(), PHP_URL_HOST),
         'version' => VERSION,
      ], $data);

      Option::set('instance', $instance);

      return $instance;
   }



   /**
    * Enqueues given JS/CSS files.
    *
    * @param array $files
    * @return void
    */
   public static function enqueue_asset_files(array $files){

      _deprecated_function('Core::enqueue_asset_files', '1.0.6', 'Util::enqueue_scripts');

      Util::enqueue_scripts($files);
   }



   /**
    * Checks whether or not the WC HPOS feature is enabled.
    *
    * @return bool
    */
   public static function is_HPOS_enabled() {

      return class_exists('Automattic\WooCommerce\Utilities\OrderUtil')
         && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();

   }

}