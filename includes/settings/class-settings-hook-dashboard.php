<?php
/**
 * Settings Hook Dashboard
 *
 * @author ConvesioPay - based on the Woosa WC integration
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Settings_Hook_Dashboard implements Interface_Hook{


   /**
    * Initiates the hooks.
    *
    * @return void
    */
   public static function init(){

      add_filter(PREFIX . '\module\settings\page\content\fields\dashboard', [__CLASS__, 'add_section_fields'], 20);

      add_action(PREFIX . '\module\settings\page\top', [__CLASS__, 'show_warning']);
   }


   /**
    * Display warning message in the page of settings.
    *
    * @param array $current_tab
    * @return string
    */
   public static function show_warning($current_tab){

      //skip Logs tab
      if('logs' === Util::array($current_tab)->get('slug') ){
         return;
      }

      // Set auth vars
      $test_mode = 'yes' == get_option( PREFIX . '_testmode' );
      if ( $test_mode ) {
         $is_authorized = 'yes' == get_option( PREFIX . '_is_authorized_test' );
      } else {
         $is_authorized = 'yes' == get_option( PREFIX . '_is_authorized_live' );
      }

      if( ! $is_authorized ):?>

         <div class="mb-20 alertbox alertbox--red">
            <?php echo self::get_warning_message();?>
         </div>

      <?php endif;
   }



   /**
    * Get the warning message.
    *
    * @return string
    */
   protected static function get_warning_message() {

      ob_start();
      ?>
      <h3><?php _e('ConvesioPay for WooCommerce is not connected to your account.', 'convesiopay-woocommerce');?></h3>
      <p><?php printf(
         __(
            'This means you have not yet authorized this store to make transactions.
            Please connect your account %shere%s or reach out to %sConvesioPay support%s for assistance.'
            , 'convesiopay-woocommerce'
         ),
         '<a href="'.SETTINGS_URL.'&tab=authorization">', '</a>',
         '<a href="mailto:pay@convesio.com">', '</a>'
      );?></p>
      <?php

      return ob_get_clean();
   }



   /**
    * Adds the fields of the section.
    *
    * @param array $fields
    * @return array
    */
   public static function add_section_fields($fields){

      $new_fields = [];

      foreach($fields as $field) {

         $new_fields[] = $field;

         if(Util::prefix('settings_start') === $field['id']) {

            
            
            
            $new_fields[] = [
               'name' => __('Remove customer\'s data', 'convesiopay-woocommerce'),
               'desc' => sprintf(__('This allows your customers to remove their personal data (%s) attached to an order payment. This only deletes the customer-related data for the specific payment, but does not cancel the existing recurring transaction.', 'convesiopay-woocommerce'), '<a href="https://gdpr-info.eu/art-17-gdpr/" target="_blank">GDPR</a>'),
               'type' => 'toggle','default' => 'no',
               'id'   => PREFIX .'_allow_remove_gdpr',
            ];
            $new_fields[] = [
               'name' => __('Include server port', 'convesiopay-woocommerce'),
               'desc' => __('Generate the client key for the shop domain by including the server port as well.', 'convesiopay-woocommerce'),
               'type' => 'toggle',
               'default' => 'yes',
               'id'   => PREFIX .'_incl_server_port',
            ];

         }
      }

      return $new_fields;
   }



   /**
    * Displays the description for `Capture mode` option.
    *
    * @since 1.0.0
    * @return void
    */
   public static function capture_desc(){

      ob_start();
      ?>

      <p class="description"><?php _e('NOTE: you have to enable this option in ConvesioPay account as well!', 'convesiopay-woocommerce');?></p>
      <p class="description"><?php _e('Manual: you need to explicitly request a capture for each payment.', 'convesiopay-woocommerce');?></p>

      <?php

      $output = str_replace(array("\r","\n"), '', trim(ob_get_clean()));

      return $output;
   }



   /**
    * Sanitizes the value before saving it.
    *
    * @since 1.1.0
    * @param string $value
    * @return string
    */
   public static function sanitize_order_reference_prefix($value){

      $value = preg_replace('/[^a-zA-Z0-9]/', '', $value);
      $value = strtoupper(substr($value, 0, 4));

      return $value;
   }

}