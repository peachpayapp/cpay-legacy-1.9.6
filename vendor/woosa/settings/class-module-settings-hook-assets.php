<?php
/**
 * Module Settings Hook Assets
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Module_Settings_Hook_Assets implements Interface_Hook_Assets{


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

      $enqueue = apply_filters(PREFIX . '\module\settings\enqueue_admin_assets', (SETTINGS_TAB_ID === Util::array($_GET)->get('page') || SETTINGS_TAB_ID === Util::array($_GET)->get('tab')), $_GET);

      if( ! $enqueue){
         return;
      }

      Util::enqueue_scripts([
         [
            'name' => 'module-settings',
            'css' => [
               'path' => untrailingslashit(plugin_dir_url(__FILE__)) . '/assets/css/',
            ],
            'js' => [
               'path' => untrailingslashit(plugin_dir_url(__FILE__)) . '/assets/js/',
               'dependency' => [PREFIX . '-module-core', PREFIX . '-jquery.tipTip.min']
            ],
         ],
         [
            'css' => [
               'name' => 'jquery.tipTip',
               'path' => untrailingslashit(plugin_dir_url(__FILE__)) . '/assets/css/',
            ],
            'js' => [
               'name' => 'jquery.tipTip.min',
               'path' => untrailingslashit(plugin_dir_url(__FILE__)) . '/assets/js/',
               'dependency' => ['jquery']
            ],
         ],
      ]);

   }


}
