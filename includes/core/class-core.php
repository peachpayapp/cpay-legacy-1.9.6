<?php
/**
 * Core
 *
 * @author ConvesioPay - based on the Woosa WC integration
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Core{


   /**
    * Clears cached payment methods.
    *
    * @since 1.1.1 - remove transient with a DB query instead of dedicated function
    *              - flush WP cache
    * @since 1.0.10
    * @return void
    */
   public static function clear_cached_payment_methods(){

      global $wpdb;

      $names = [
         PREFIX . '_is_active_',
         PREFIX . '_payment_method',
         PREFIX . '_stored_payment_methods_',
      ];

      foreach($names as $name){
         $wpdb->query("
            DELETE
               FROM `$wpdb->options`
            WHERE `option_name`
               LIKE ('_transient_$name%')
            OR `option_name`
               LIKE ('_transient_timeout_$name%')
         ");
      }

      wp_cache_flush();
   }



   /**
    * Updates cached payment methods.
    *
    * @since 1.0.10
    * @return void
    */
   public static function update_cached_payment_methods(){

      self::clear_cached_payment_methods();

      Service::checkout()->list_payment_methods();
   }



   /**
    * Gets the client IP
    *
    * @since 1.0.0
    * @return string
    */
   public static function get_client_ip(){

      $ip = Util::array($_SERVER)->get('HTTP_X_FORWARDED_FOR');

      if(empty($ip)){
         $ip = Util::array($_SERVER)->get('HTTP_CLIENT_IP');
      }

      if(empty($ip)){
         $ip = Util::array($_SERVER)->get('REMOTE_ADDR');
      }

      return $ip;//'2.56.212.0'
   }



   /**
    * Gets local language.
    *
    * @return string
    */
   public static function get_locale(){

      $locale = array_filter(explode(',', Util::array($_SERVER)->get('HTTP_ACCEPT_LANGUAGE', '')));

      if( ! empty($locale[0]) ){
         return $locale[0];
      }

      return str_replace('_', '-', get_locale());//get WP locale
   }

}