<?php
/**
 * Module Core Hook Assets
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Module_Core_Hook_Assets implements Interface_Hook_Assets{


   /**
    * Initiates.
    *
    * @return void
    */
   public static function init(){

      add_action('admin_enqueue_scripts', [__CLASS__ , 'admin_assets']);
   }



   /**
    * Enqueues public CSS/JS files.
    *
    * @return void
    */
   public static function public_assets(){}



   /**
    * Enqueues admin CSS/JS files.
    *
    * @return void
    */
   public static function admin_assets(){

      add_thickbox();

      Util::enqueue_scripts([
         [
            'name' => 'jquery.blockUI.min',
            'js' => [
               'path' => untrailingslashit(plugin_dir_url(__FILE__)) . '/assets/js/',
               'dependency' => ['jquery']
            ],
         ],
         [
            'name' => 'sweetalert.min',
            'js' => [
               'path' => untrailingslashit(plugin_dir_url(__FILE__)) . '/assets/js/',
               'dependency' => ['jquery']
            ],
         ],
         [
            'name' => 'module-core-grid',
            'css' => [
               'path' => untrailingslashit(plugin_dir_url(__FILE__)) . '/assets/css/',
            ],
         ],
         [
            'name' => 'module-core',
            'css' => [
               'path' => untrailingslashit(plugin_dir_url(__FILE__)) . '/assets/css/',
            ],
            'js' => [
               'path' => untrailingslashit(plugin_dir_url(__FILE__)) . '/assets/js/',
               'dependency' => ['jquery'],
               'localize' => true,
            ],
         ],
      ]);
   }
}