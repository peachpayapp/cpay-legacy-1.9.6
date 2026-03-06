<?php
/**
 * Util Array
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Util_Array{


   /**
    * The input array.
    *
    * @var array
    */
   public $array = [];



   /**
    * Construct of this class.
    *
    * @param array $array
    */
   public function __construct($array){

      $this->array = $array;
   }



   /**
    * Gets a specific property of an array without needing to check if that property exists.
    *
    * @param string $property
    * @param string $default
    * @param int $filter_sanitize
    * @return mixed
    */
   public function get( $property, $default = null, $filter_sanitize = FILTER_DEFAULT ){

      if(strpos($property, '/') !== false){
         $raw_value = $this->get_raw_multi($property, $default);
      }else{
         $raw_value = $this->get_raw($property, $default);
      }

      if($filter_sanitize === false){

         $value = $raw_value;

      }else{

         if(is_string($raw_value)){
            $value = filter_var($raw_value, $filter_sanitize );
         }else{
            $value = $raw_value;
         }
      }

      return $value;
   }



   /**
    * Gets a sanitized email string.
    *
    * @param string $property
    * @param string $default
    * @return string
    */
   public function get_email( $property, $default = null ){
      return sanitize_email( filter_var( $this->get_raw($property, $default), FILTER_SANITIZE_EMAIL) );
   }



   /**
    * Gets a sanitized URL string.
    *
    * @param string $property
    * @param string $default
    * @return string
    */
   public function get_url( $property, $default = null ){
      return esc_url( $this->get_raw($property, $default) );
   }



   /**
    * Gets a sanitized string which can be used as an internal identifiers.
    *
    * @param string $property
    * @param string $default
    * @return string
    */
   public function get_key( $property, $default = null ){
      return sanitize_key( $this->get_raw($property, $default) );
   }



   /**
    * Gets a sanitized string which can be used as HTML class value.
    *
    * @param string $property
    * @param string $default
    * @return string
    */
   public function get_html_class( $property, $default = null ){
      return sanitize_html_class( $this->get_raw($property, $default) );
   }



   /**
    * Gets sanitized content for allowed HTML tags for post content.
    *
    * @param string $property
    * @param string $default
    * @return string
    */
   public function get_post_content( $property, $default = null ){
      return wp_kses_post( $this->get_raw($property, $default) );
   }



   /**
    * Gets a specific property of an array without needing to check if that property exists.
    *
    * @param string $property
    * @param string $default
    * @return string|array
    */
   protected function get_raw( $property, $default = null){

      if ( ! is_array( $this->array ) && ! ( is_object( $this->array ) && $this->array instanceof ArrayAccess ) ) {
         return $default;
      }

      if ( array_key_exists( $property, $this->array ) ) {

         return $this->array[$property];

      }elseif ( array_key_exists( Util::prefix($property), $this->array ) ) {

         return $this->array[Util::prefix($property)];

      }elseif ( array_key_exists( Util::prefix($property, true), $this->array ) ) {

         return $this->array[Util::prefix($property, true)];

      }

      return $default !== null ? $default : '';
   }



   /**
    * Gets a specific property within a multidimensional array.
    *
    * @param string $property
    * @param string $default
    * @return string|array
    */
   protected function get_raw_multi($property, $default = null){

      if ( ! is_array( $this->array ) && ! ( is_object( $this->array ) && $this->array instanceof ArrayAccess ) ) {
         return $default;
      }

      $names = explode( '/', $property );
      $value = $this->array;

      foreach ( $names as $current_name ) {
         $value = (new Util_Array($value))->get_raw($current_name, $default );
      }

      return $value;
   }



   /**
    * Returns an unique value array by checking specific key inside the value
    *
    * @param string $key
    * @return array
    */
   public function unique_by_key($key) {

      $i = 0;
      $temp_array = [];
      $key_array = [];

      foreach( $this->array as $val ) {
         if ( ! in_array( $val[$key], $key_array ) ) {
            $key_array[$i] = $val[$key];
            $temp_array[$i] = $val;
         }
         $i++;
      }
      return $temp_array;
   }



   /**
    * Case insensitive search for substring in array values and return first occurence key
    *
    * @param string $needle
    * @return void
    */
   public function search_substring($needle) {

      $result = false;

      foreach ( $this->array as $key => $value ) {
         if ( stripos( $value, $needle ) !== FALSE) {
            $result = $key;
            break;
         }
      }
      return $result;

   }


}
