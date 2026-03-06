<?php
/**
 * Bancontact
 *
 * Payment type     : Debit Card
 * Payment flow     : Redirect
 * Countries        : BE
 * Currencies       : EUR
 * Recurring        : Yes
 * Refunds          : Yes
 * Partial refunds  : Yes
 * Separate captures: No
 * Partial captures : No
 * Chargebacks      : No
 *
 * @author ConvesioPay - based on the Woosa WC integration
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Bancontact extends Credit_Card{


   /**
    * List of countries where is available.
    *
    * @since 1.1.0
    * @return array
    */
   public function available_countries(){

      return [
         'BE' => [
            'currencies' => ['EUR'],
         ],
      ];
   }



   /**
    * Gets default payment method title.
    *
    * @since 1.0.0
    * @return string
    */
   public function get_default_title(){
      return __('ConvesioPay - Bancontact', 'convesiopay-woocommerce');
   }



   /**
    * Type of the payment method (e.g ideal, scheme. bcmc).
    *
    * @since 1.0.0
    * @return string
    */
   public function payment_method_type(){
      return 'bcmc';
   }



   /**
    * Returns the payment method to be used for recurring payments
    *
    * @since 1.0.0
    * @return string
    */
   public function recurring_payment_method(){
      return 'sepadirectdebit';
   }



   /**
    * Adds extra fields.
    *
    * @since 1.0.0
    * @return void
    */
    public function payment_fields() {

      Abstract_Gateway::payment_fields();

      echo $this->generate_extra_fields_html();

   }



   /**
    * Generates extra fields HTML.
    *
    * @since 1.1.1 - use the real method type in field names
    * @since 1.0.0
    * @return string
    */
   public function generate_extra_fields_html(){

      $type = $this->payment_method_type();
      ?>
      <div class="<?php echo PREFIX;?>-wrap-form">

         <?php $this->render_card_form();?>

         <input type="hidden" id="<?php echo esc_attr(PREFIX . "-{$type}-card-number");?>" name="<?php echo esc_attr(PREFIX . "-{$type}-card-number");?>" />
         <input type="hidden" id="<?php echo esc_attr(PREFIX . "-{$type}-card-brand");?>" name="<?php echo esc_attr(PREFIX . "-{$type}-card-brand");?>" />
         <input type="hidden" id="<?php echo esc_attr(PREFIX . "-{$type}-card-exp-month");?>" name="<?php echo esc_attr(PREFIX . "-{$type}-card-exp-month");?>" />
         <input type="hidden" id="<?php echo esc_attr(PREFIX . "-{$type}-card-exp-year");?>" name="<?php echo esc_attr(PREFIX . "-{$type}-card-exp-year");?>" />
         <input type="hidden" id="<?php echo esc_attr(PREFIX . "-{$type}-card-cvc");?>" name="<?php echo esc_attr(PREFIX . "-{$type}-card-cvc");?>" />
         <input type="hidden" id="<?php echo esc_attr(PREFIX . "-{$type}-card-holder");?>" name="<?php echo esc_attr(PREFIX . "-{$type}-card-holder");?>" />
         <input type="hidden" id="<?php echo esc_attr(PREFIX . "-{$type}-sci");?>" name="<?php echo esc_attr(PREFIX . "-{$type}-sci");?>" />
         <input type="hidden" id="<?php echo esc_attr(PREFIX . "-{$type}-store-card");?>" name="<?php echo esc_attr(PREFIX . "-{$type}-store-card");?>" />
         <input type="hidden" id="<?php echo esc_attr(PREFIX . "-{$type}-is-stored-card");?>" name="<?php echo esc_attr(PREFIX . "-{$type}-is-stored-card");?>" value="yes" />
      </div>
      <?php
   }


}