<?php
/**
 * Dependency Hook
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Dependency_Hook implements Interface_Hook{


   /**
    * Initiates the hooks.
    *
    * @return void
    */
   public static function init(){

      add_filter(PREFIX . '\dependency\wp_plugins', [__CLASS__, 'wp_plugins']);
   }



   /**
    * Addes WP dependency plugins.
    *
    * @param array $items
    * @return array
    */
   public static function wp_plugins($items){

      $items['woocommerce/woocommerce.php'] = [
         'name'    => 'WooCommerce',
         'version' => '5.0',
      ];

      return $items;
   }
}