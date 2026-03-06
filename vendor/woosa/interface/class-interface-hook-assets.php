<?php
/**
 * Interface Hook Assets
 *
 * This interface is dedicated for including CSS or JS files.
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


interface Interface_Hook_Assets{


   /**
    * Initiates the hooks.
    *
    * @return void
    */
   public static function init();


   /**
    * Enqueues public CSS/JS files.
    *
    * @return void
    */
   public static function public_assets();



   /**
    * Enqueues admin CSS/JS files.
    *
    * @return void
    */
   public static function admin_assets();
}