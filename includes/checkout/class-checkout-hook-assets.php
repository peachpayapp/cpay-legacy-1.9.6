<?php
/**
 * Checkout Hook Assets
 *
 * @author ConvesioPay - based on the Woosa WC integration
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Checkout_Hook_Assets implements Interface_Hook_Assets{


   /**
    * Initiates.
    *
    * @return void
    */
   public static function init(){

      add_action('wp_enqueue_scripts', [__CLASS__ , 'public_assets'], 99);

   }



   /**
    * Enqueues admin CSS/JS files.
    *
    * @return void
    */
   public static function public_assets(){

      if(is_checkout()){

         $api = new Service;
         $env = $api->get_env();
         $google_gateway = new Googlepay();

         Util::enqueue_scripts([
            [
               'js' => [
                  'handle' => 'jquery-ui-datepicker',
                  'register' => false,
               ]
            ],
            [
               'css' => [
                  'name' => 'adyen.min',
                  'path' => untrailingslashit(plugin_dir_url(__FILE__)) . '/assets/css',
                  'version' => '5.36.0',
               ],
               'js' => [
                  'name' => 'adyen-'.$env.'.min',
                  'path' => untrailingslashit(plugin_dir_url(__FILE__)) . '/assets/js',
                  'dependency' => [PREFIX . '-util'],
                  'version' => '5.36.0',
               ],
            ],
            [
               'name' => 'jquery.popupoverlay',
               'js' => [
                  'path' => untrailingslashit(plugin_dir_url(__FILE__)) . '/assets/js',
                  'dependency' => [PREFIX . '-util'],
               ],
            ],
            [
               'name' => 'checkout',
               'css' => [
                  'path' => untrailingslashit(plugin_dir_url(__FILE__)) . '/assets/css',
               ],
               'js' => [
                  'path' => untrailingslashit(plugin_dir_url(__FILE__)) . '/assets/js',
                  'dependency' => [PREFIX . '-adyen-'.$env.'.min'],
                  'localize' => [
                     'google_method_type' => $google_gateway->payment_method_type(),
                  ]
               ],
            ],
         ]);
      }

   }



   /**
    * Enqueues public CSS/JS files.
    *
    * @return void
    */
   public static function admin_assets(){}

}