<?php
/**
 * Option
 *
 * Utility class for working with WP options.
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Option{


   /**
    * Retrieves an option value based on an option name.
    *
    * @param string $name
    * @param boolean $default
    * @param boolean $prefix - whether or not to use the prefix
    * @return mixed
    */
   public static function get($name, $default = false, $prefix = true){

      $name  = $prefix ? Util::prefix($name) : $name;
      $value = get_option($name, $default);

      return $value;
   }



   /**
    * Sets/updates the value of an option.
    *
    * @param string $name
    * @param mixed $value
    * @param boolean $autoload
    * @param boolean $prefix - whether or not to use the prefix
    * @return bool
    */
   public static function set($name, $value, $autoload = false, $prefix = true){

      $name = $prefix ? Util::prefix($name) : $name;

      return update_option($name, $value, $autoload);

   }



   /**
    * Removes an option.
    *
    * @param string $name
    * @param boolean $prefix - whether or not to use the prefix
    * @return bool
    */
   public static function delete($name, $prefix = true){

      $name = $prefix ? Util::prefix($name) : $name;

      return delete_option($name);

   }



   /**
    * Checks whether or not the given option field (especially checkboxes) was checked (values: true/false or yes/no).
    *
    * @param string $name
    * @param string|false $default
    * @param boolean $prefix
    * @return boolean
    */
   public static function is_checked($name, $default = false, $prefix = true){
      return Util::string_to_bool( self::get($name, $default, $prefix) );
   }

}
