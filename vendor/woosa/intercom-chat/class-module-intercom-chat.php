<?php
/**
 * Module Intercom Chat
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Module_Intercom_Chat {


   const APP_ID = 'yfmyrdz3';

   const API_BASE = 'https://api-iam.eu.intercom.io';


   /**
    * Get the app id
    *
    * @return string
    */
   public static function get_app_id() {
      return self::APP_ID;
   }



   /**
    * Get api base url
    *
    * @return string
    */
   public static function get_api_base() {
      return self::API_BASE;
   }



   /**
    * Get email address
    *
    * @return string
    */
   public static function get_user_email() {

      $license_key = Option::get('license_key', '');

      $iv = 'ab86d144ab86d144';
      $cipher = "aes-128-ctr";

      if ( ! empty($license_key) && ( strlen( $license_key ) % 2 == 0 ) && ctype_xdigit( $license_key ) ) {

         $license_decoded = openssl_decrypt( hex2bin($license_key), $cipher, '', OPENSSL_RAW_DATA, $iv );

         $license_decoded_parts = explode('*', $license_decoded);

         $email = Util::array($license_decoded_parts)->get(1);

         return is_email($email) ? $email : '';

      }

      return '';

   }


}
