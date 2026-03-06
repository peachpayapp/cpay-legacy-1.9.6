<?php
/**
 * Module Third Party
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Module_Third_Party{


   /**
    * Initiates.
    *
    * @return void
    */
   public static function init(){

      add_action('admin_init', [__CLASS__, 'toggle_ACF_metabox_visibility']);
   }



   /**
    * Displays ACF metabox if our debug mode is enabled.
    *
    * @return void
    */
   public static function toggle_ACF_metabox_visibility(){

      if(DEBUG){
         add_filter('acf/settings/remove_wp_meta_box', '__return_false');
      }
   }


}