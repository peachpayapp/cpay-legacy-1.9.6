<?php
/**
 * Interface Hook Settings Section
 *
 * This interface is dedicated for adding a new section in a tab via `Settings` module hooks.
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


interface Interface_Hook_Settings_Section{


   /**
    * The id of the tab.
    *
    * @return string
    */
   public static function tab_id();



   /**
    * Initiates the hooks.
    *
    * @return void
    */
   public static function init();



   /**
    * Initiates the tab under a condition.
    *
    * @return void
    */
   public static function maybe_init();



   /**
    * Adds the fields of the section.
    *
    * @param array $items
    * @return array
    */
   public static function add_section_fields(array $items);
}