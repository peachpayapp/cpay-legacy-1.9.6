<?php
/**
 * SEPA Direct Debit payment method
 *
 * This class creates SEPA Direct Debit payment method.
 *
 * @author ConvesioPay - based on the Woosa WC integration
 * @since 1.0.0
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Sepa_Direct_Debit extends Abstract_Gateway{


   /**
    * Constructor of this class.
    *
    * @param bool $init_hooks
    * @since 1.0.0
    */
    public function __construct($init_hooks = true){

      parent::__construct($init_hooks);

      $this->has_fields = true;
      $this->currencies = ['EUR'];
   }



   /**
    * Gets default payment method title.
    *
    * @since 1.0.0
    * @return string
    */
   public function get_default_title(){
      return __('ConvesioPay - SEPA Direct Debit', 'convesiopay-woocommerce');
   }



   /**
    * Gets default payment method description.
    *
    * @since 1.0.0
    * @return string
    */
   public function get_default_description(){
      return __('SEPA Direct Debit is used for recurring payments with WooCommerce Subscriptions, and will not be shown in the WooCommerce checkout.', 'convesiopay-woocommerce');
   }



   /**
    * Gets default description set in settings.
    *
    * @since 1.0.0
    * @return string
    */
   public function get_settings_description(){}



   /**
    * Type of the payment method (e.g ideal, scheme. bcmc).
    *
    * @since 1.0.0
    * @return string
    */
   public function payment_method_type(){
      return 'sepadirectdebit';
   }



   /**
    * Returns the payment method to be used for recurring payments
    *
    * @since 1.0.0
    * @return string
    */
   public function recurring_payment_method(){
      return $this->payment_method_type();
   }



   /**
	 * Checks if the gateway is available for use.
	 *
    * @since 1.0.0
	 * @return bool
	 */
	public function is_available() {

      //we do not want to show SEPA in checkout page
      return false;
   }


}