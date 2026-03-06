<?php
/**
 * Module Field Generator Hook Assets
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Module_Field_Generator_Hook_Assets implements Interface_Hook_Assets{


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

      wp_enqueue_style(
         PREFIX . '-quill',
         'https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css',
         [],
         '2.0.2',
      );

      wp_enqueue_script(
         PREFIX . '-quill',
         'https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js',
         ['jquery'],
         '2.0.2',
      );

      Util::enqueue_scripts([
         [
            'name' => 'field-generator',
            'css' => [
               'path' => untrailingslashit(plugin_dir_url(__FILE__)) . '/assets/css/',
            ],
         ],
         [
            'name' => 'module-field-generator',
            'js' => [
               'path' => untrailingslashit(plugin_dir_url(__FILE__)) . '/assets/js/',
               'dependency' => ['jquery', PREFIX . '-quill'],
               'localize' => true,
            ],
         ],
      ]);
   }
}