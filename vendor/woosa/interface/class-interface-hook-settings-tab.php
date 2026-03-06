<?php
/**
 * Interface Hook Settings Tab
 *
 * This interface is dedicated for adding a new settings tab via `Settings` module hooks.
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


interface Interface_Hook_Settings_Tab{


   /**
    * The id of the tab.
    *
    * @return string
    */
   public static function id();



   /**
    * The name of the tab.
    *
    * @return string
    */
   public static function name();



   /**
    * The description of the tab.
    *
    * @return string
    */
   public static function description();



   /**
    * The icon URL of the tab.
    *
    * @return string
    */
   public static function icon_url();



   /**
    * Initiates the hooks.
    *
    * @return void
    */
   public static function init();



   /**
    * Initiates the tab conditionally.
    *
    * @return void
    */
   public static function maybe_init();



   /**
    * Adds the tab in the list.
    *
    * @param array $tabs
    * @return array
    */
   public static function add_tab(array $tabs);



   /**
    * Adds the fields of the tab.
    *
    * @param array $fields
    * @return array
    */
   public static function add_tab_fields(array $fields);
}