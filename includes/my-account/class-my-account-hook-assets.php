<?php
/**
 * My Account Hook Assets
 *
 * @author ConvesioPay - based on the Woosa WC integration
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class My_Account_Hook_Assets implements Interface_Hook_Assets{


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

      if(is_account_page()){

         Util::enqueue_scripts([
            [
               'name' => 'my-account',
               'css' => [
                  'path' => untrailingslashit(plugin_dir_url(__FILE__)) . '/assets/css/',
               ],
               'js' => [
                  'path' => untrailingslashit(plugin_dir_url(__FILE__)) . '/assets/js/',
                  'dependency' => [PREFIX . '-util'],
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