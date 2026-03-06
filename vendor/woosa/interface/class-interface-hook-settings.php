<?php
/**
 * Interface Hook Settings
 *
 * This interface is dedicated for adding a new settings section via `Settings` module hooks.
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


interface Interface_Hook_Settings{


   /**
    * The id of the section.
    *
    * @return string
    */
   public static function section_id();



   /**
    * The name of the section.
    *
    * @return string
    */
   public static function section_name();



   /**
    * Initiates the hooks.
    *
    * @return void
    */
   public static function init();



   /**
    * Initiates the section under a condition.
    *
    * @return void
    */
   public static function maybe_init();



   /**
    * Adds the section in the list.
    *
    * @param array $items
    * @return array
    */
   public static function add_section(array $items);



   /**
    * Adds the fields of the section.
    *
    * @param array $items
    * @return array
    */
   public static function add_section_fields(array $items);



   /**
    * Useful in conjunction with the hook `woocommerce_admin_field_{$field}` to completely render a custom content in the section.
    *
    * @param array $values
    * @return string
    */
   public static function output_section(array $values);
}