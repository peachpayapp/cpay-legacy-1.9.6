<?php
/**
 * Transient
 *
 * Utility class for working with WP transients.
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Transient{


   /**
    * Retrieves the value of a transient.
    * If the transient does not exist, does not have a value, or has expired, then the return value will be false.
    *
    * @param string $name
    * @param boolean $prefix - whether or not to use the prefix
    * @return mixed
    */
   public static function get($name, $prefix = true){

      $name  = $prefix ? Util::prefix($name) : $name;
      $value = get_transient($name);

      return $value;
   }



   /**
    * Retrieves the expire time for the given transient.
    *
    * @param string $name
    * @param boolean $prefix
    * @return int
    */
   public static function get_expire_time($name, $prefix = true){

      $name = $prefix ? Util::prefix($name) : $name;

      return (int) get_option("_transient_timeout_{$name}", 0);
   }



   /**
    * Sets/updates the value of a transient.
    *
    * @param string $name
    * @param mixed $value
    * @param int $expiration
    * @param boolean $prefix - whether or not to use the prefix
    * @return bool
    */
   public static function set($name, $value, $expiration, $prefix = true){

      $name = $prefix ? Util::prefix($name) : $name;

      return set_transient($name, $value, $expiration);

   }



   /**
    * Removes a transient.
    *
    * @param string $name
    * @param boolean $prefix - whether or not to use the prefix
    * @return bool
    */
   public static function delete($name, $prefix = true){

      $name = $prefix ? Util::prefix($name) : $name;

      return delete_transient($name);

   }

}
