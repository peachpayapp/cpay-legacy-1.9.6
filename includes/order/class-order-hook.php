<?php
/**
 * Order Hook
 *
 * @author ConvesioPay - based on the Woosa WC integration
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Order_Hook implements Interface_Hook{


   /**
    * Initiates the hooks.
    *
    * @return void
    */
   public static function init(){

      add_action('woocommerce_admin_order_data_after_order_details', [__CLASS__, 'capture_payment_button']);

      add_action('woocommerce_order_details_before_order_table', [__CLASS__, 'show_remove_personal_data']);

      add_filter('wcs_subscription_meta', [__CLASS__, 'exclude_particular_meta'], 10, 3);
   }



   /**
    * Renders capture payment button.
    *
    * @since 1.1.0 - add capture button for manual payment methods
    * @since 1.0.3
    * @param \WC_Order $order
    * @return string
    */
   public static function capture_payment_button($order){

      $capture     = get_option(PREFIX.'_capture_payment', 'immediate');
      $is_captured = $order->get_meta('_'.PREFIX.'_payment_captured');
      $statuses    = ['processing', 'on-hold'];
      $payment_method_type = str_replace('woosa_adyen_', '', $order->get_payment_method()); //replace the prefix

      if(
         ! in_array($order->get_status(), $statuses) ||
         'yes' === $is_captured ||
         ('immediate' === $capture && ! Service_Util::is_manual_payment($payment_method_type)) ||
         strpos($order->get_payment_method(), 'woosa_adyen') === false
      ){
         return;
      }

      ?>
      <div class="form-field form-field-wide">
         <h3><?php _e('Capture Payment', 'convesiopay-woocommerce');?></h3>
         <p><?php _e('By pressing this button you will request Adyen to capture the payment for this order.', 'convesiopay-woocommerce');?></p>
         <p>
            <button type="button" class="button" data-capture-order-payment="<?php echo esc_attr($order->get_id());?>"><?php _e('Capture', 'convesiopay-woocommerce');?></button>
         </p>
      </div>
      <?php
   }



   /**
    * Displays the section for removing the personal data.
    *
    * @since 1.1.0
    * @param \WC_Order $order
    * @return string
    */
   public static function show_remove_personal_data($order){

      $payment_method = $order->get_payment_method();
      $is_removed = $order->get_meta('_' . PREFIX . '_gdpr_removed');

      $allow_removal = get_option(PREFIX .'_allow_remove_gdpr');

      if(strpos($payment_method, 'woosa_adyen') === false || 'yes' !== $allow_removal) return;

      ?>
      <div>
         <h2 class="woocommerce-order-details__title woocommerce-order-details__title--data-protection"><?php _e('General Data Protection Regulation (GDPR)', 'convesiopay-woocommerce');?></h2>
         <?php if('yes' === $is_removed):?>
            <p><?php _e('The personal and payment information attached to this order have been removed.', 'convesiopay-woocommerce');?></p>
         <?php else:?>
            <p><?php printf(__('This will erase your personal and payment information attached to this order according to %sGDPR%s.', 'convesiopay-woocommerce'), '<a href="https://gdpr-info.eu/art-17-gdpr/" target="_blank">', '</a>');?></p>
            <p>
               <button type="button" class="button" data-remove-gdpr="<?php echo esc_attr($order->get_id());?>"><?php _e('Yes remove', 'convesiopay-woocommerce');?></button>
            </p>
         <?php endif;?>
      </div>
      <?php

   }



   /**
    * Excludes particular meta to be copied from the order.
    *
    * @param array $meta
    * @param \WC_Subscription $to_order
    * @param \WC_Order $from_order
    * @return array
    */
   public static function exclude_particular_meta($meta, $to_order, $from_order){

      $exclude = [
         '_' . PREFIX . '_payment_pspReference',
         '_' . PREFIX . '_payment_resultCode',
         '_' . PREFIX . '_payment_action',
         '_' . PREFIX . '_unpaid_subscriptions',
      ];

      foreach($meta as $index => $item){
         if( in_array($item['meta_key'], $exclude) ){
            unset($meta[$index]);
         }
      }

      return $meta;
   }

}