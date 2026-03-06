<?php
/**
 * Util Price
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Util_Price{


   /**
    * The price value.
    *
    * @var string
    */
   protected $price = '0';



   /**
    * Construct of this class.
    *
    * @param string $price
    */
   public function __construct($price){
      $this->price = $price;
   }



   /**
    * Return formatted price using WC settings
    *
    * @return string
    */
   public function wc_format(){

      if(function_exists('\\wc_get_price_decimals') && function_exists('\\wc_get_price_decimal_separator') && function_exists('\\wc_get_price_thousand_separator')){

         if(is_numeric($this->price)){
            $this->price = number_format( floatval( $this->price ), wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator() );
         }
      }

      return $this->price;
   }



   /**
    * Return normalized price using WC settings
    *
    * @return string
    */
   public function wc_normalize() {

      if(function_exists('\\wc_get_price_decimal_separator') && function_exists('\\wc_get_price_thousand_separator')){

         if(is_numeric($this->price)){
            $this->price = str_replace(wc_get_price_decimal_separator(), '.', str_replace(wc_get_price_thousand_separator(), '', $this->price));
         }
      }

      return $this->price;
   }



   /**
    * Formats price by keeping only a dot for decimals
    *
    * @return string
    */
   public function format(){

      $this->price = str_replace(',', '.', $this->price);
      $this->price = preg_replace('/\.(?=.*\.)/', '', $this->price);

      return $this->price;
   }



   /**
    * Calculates the price based on the given discount.
    *
    * @param float $percentage
    * @param integer $decimals
    * @return string
    */
   public function discount(float $percentage, int $decimals = 2){

      if(is_numeric($this->price)){

         if($percentage > 0){
            $this->price = $this->price - ($percentage / 100 * $this->price);
         }else{
            $this->price = 0;
         }

         $this->price = number_format((float) $this->price, $decimals, ".", "");
      }

      return $this->price;

   }



   /**
    * Calculates the price with the given additon.
    *
    * @param string $addition - can be number: 10 or percentage: 10%
    * @param integer $decimals
    * @return string
    */
   public function addition(string $addition, int $decimals = 2){

      if(is_numeric($this->price)){

         if(strpos($addition, '%') !== false){

            $addition = (float) $addition;
            $addition =  $addition > 0 ? $addition / 100 : 0;

            $this->price += $this->price * $addition;

         }else{

            $this->price += (float) $addition;
         }

         $this->price = number_format((float) $this->price, $decimals, ".", "");
      }

      return $this->price;
   }



   /**
    * Rounds the price up based on the given rounding mode.
    *
    * @param string $mode
    * @param integer $decimals
    * @return string
    */
   public function round_up(string $mode, int $decimals = 2){

      if ( is_numeric($this->price) && $this->price > 0 ) {

         switch ($mode) {

            //20.17 -> 20.25
            case '.75':

               $this->price = ceil( (float) $this->price / 0.25 ) * 0.25;
               break;

            //20.17 -> 20.99
            case '.99':
               $this->price = ceil( (float) $this->price ) - .01;
               break;

            //20.17 -> 21.00
            case '.00':
               $this->price = ceil( (float) $this->price );
               break;
         }

         $this->price = number_format( (float) $this->price, $decimals, '.', '');
      }

      return $this->price;

   }
}