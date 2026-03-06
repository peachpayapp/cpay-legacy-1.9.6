<?php
/**
 * Settings Hook Webhooks
 *
 * @author ConvesioPay - based on the Woosa WC integration
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Cpay_Settings_Hook implements Interface_Hook_Settings{


   /**
    * The id of the section.
    *
    * @return string
    */
   public static function section_id(){
      return 'settings-cpay';
   }



   /**
    * The name of the section.
    *
    * @return string
    */
   public static function section_name(){
      return __('Settings ConvesioPay', 'convesiopay');
   }



   public static function cpay_enqueue_scripts($screen) {
      $parts = parse_url( wc_get_current_admin_url() );
      if ( isset($parts['query']) ) {
         parse_str($parts['query'], $query);
      }
      $page = isset( $query['page'] ) ? $query['page'] : '';
      $section = isset( $query['section'] ) ? $query['section'] : '';


      if ( 'convesiopay' == $page ) {
         add_thickbox();
         wp_enqueue_script('cpay-admin-script', DIR_URL . '/includes/cpay/assets/js/settings-cpay.js', array(), null, true);
         wp_enqueue_style('cpay-admin-style', DIR_URL . '/includes/cpay/assets/css/settings-cpay.css', false, '1.0.0');
      } else if ('woosa_adyen_applepay' == $section || 'woosa_adyen_googlepay' == $section) {
         wp_enqueue_style('cpay-admin-overrides', DIR_URL . '/includes/cpay/assets/css/settings-overrides.css', false, '1.0.0');
      } else {
         wp_enqueue_script('cpay-menu-icon', DIR_URL . '/includes/cpay/assets/js/show-cpay-icon.js');
      }
   }



   /**
    * Initiates the hooks.
    *
    * @return void
    */
   public static function init(){

      add_filter('woocommerce_admin_settings_sanitize_option_' . PREFIX . '_order_reference_prefix', [__CLASS__, 'sanitize_order_reference_prefix']);
      add_action('admin_enqueue_scripts', [__CLASS__ , 'cpay_enqueue_scripts'], 99);
      add_action( 'wp_ajax_cpay_authorize', [__CLASS__, 'cpay_authorize'] );
      add_action( 'wp_ajax_cpay_connect_modal', [__CLASS__, 'cpay_connect_modal'] );
      add_action( 'wp_ajax_cpay_disconnect_modal', [__CLASS__, 'cpay_disconnect_modal'] );
      // Ensure ConvesioPay is shown as the payment method on cpay CC subscriptions
      add_filter('woocommerce_subscription_payment_method_to_display', function($payment_method_to_display, $subscription) {
          // Only add the tooltip on the single subscription details page in admin
          if (
              is_admin() &&
              function_exists('get_current_screen') &&
              ($screen = get_current_screen()) &&
              $screen->id === 'shop_subscription'
          ) {
              if ($subscription->get_payment_method() === 'woosa_adyen_credit_card' &&
                  $subscription->get_meta('_cpay_recurringDetailReference')) {
                  return 'ConvesioPay - Credit Card <span class="woocommerce-help-tip" data-tip="Gateway ID: [cpay_credit_card]"></span>';
              }
          } else {
              // For all other contexts (like the subscriptions table), just show plain text
              if ($subscription->get_payment_method() === 'woosa_adyen_credit_card' &&
                  $subscription->get_meta('_cpay_recurringDetailReference')) {
                  return 'ConvesioPay - Credit Card';
              }
          }
          return $payment_method_to_display;
      }, 10, 2);

   }



   /**
    * Initiates the section under a condition.
    *
    * @return void
    */
   public static function maybe_init(){}



   /**
    * Adds the section in the list.
    *
    * @param array $items
    * @return array
    */
   public static function add_section($items){

      $items[self::section_id()] = self::section_name();

      return $items;
   }



  /**
    * Adds the fields of the section.
    *
    * @param array $fields
    * @return array
    */
   public static function add_section_fields($items){
      return $items;
   }



   /**
    * Authorizes the store to make transactions with ConvesioPay
    *
    * @return string $apiKey
    */
   public static function cpay_authorize() {
      $test_mode = 'yes' == get_option( PREFIX . '_testmode' );
      $auth_host = $test_mode ? 'dev.convesiopay.com' : 'convesiopay.com';
      $cpay_auth_url = 'https://' . $auth_host . '/api/integration/request-api-key';

      // Request ConvesioPay API Key
      $data = array(
         'email' => sanitize_text_field( $_POST['email'] ),
         'password' => sanitize_text_field( $_POST['password'] ),
         'requestingStoreUrl' => esc_url( $_POST['requestingStoreUrl'] ),
         'requestingStoreWebhook' => esc_url( $_POST['requestingStoreWebhook'] ),
         'adminClientKey' => sanitize_text_field( $_POST['adminClientKey'] )
      );
      $data_json = json_encode($data);
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $cpay_auth_url);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
      $res = json_decode( curl_exec($ch), true );
      curl_close($ch);

      if ( isset($res['apiKey']) ) {
         // Set ConvesioPay API Key
         update_option(PREFIX . '_convesiopay_api_key', $res['apiKey']);

         // Set capture mode (must be set to manual, in practice both auto-capture and manual-capture are supported based on merchant payment settings at convesiopay.com)
         update_option(PREFIX . '_capture_payment', 'manual');

         // Set mode based options
         if ( $test_mode ) {
            // Test settings
            update_option(PREFIX . '_testmode', 'yes');
            update_option(PREFIX . '_api_username', 'convesiopay');
            update_option(PREFIX . '_api_password', '0xUR0qCV1oAZKZ5R1fUducgA6asWIDWm');
            update_option(PREFIX . '_hmac_key', '98DBD4A20B4BFDC873A284D67700C32E03503C6D559BFF464C168F31C164F4AF'); 
         } else {
            // Prod settings
            update_option(PREFIX . '_testmode', 'no');
            update_option(PREFIX . '_api_username', 'convesiopay-prod');
            update_option(PREFIX . '_api_password', 'VhAF6Z4RpZcnUx4i3UaUtJRHI8lZajkG');
            update_option(PREFIX . '_hmac_key', '64550E47783E9F420594CE0457625DA526EAC7FBAD7DB28415DFA265468C7807');
            update_option(PREFIX . '_url_prefix', '51357d7d79613e33-Convesio');
         }

         wp_send_json_success();
      } else {
         wp_send_json_error($res);
      }

      wp_die();
   }



   /**
    * Serves the connect modal content via AJAX
    *
    * @return void
    */
   public static function cpay_connect_modal() {
      // Include the connect.php content
      include DIR_PATH . '/includes/cpay/connect-account/connect.php';
      wp_die();
   }

   /**
    * Serves the disconnect modal content via AJAX
    *
    * @return void
    */
   public static function cpay_disconnect_modal() {
      // Include the disconnect.php content
      include DIR_PATH . '/includes/cpay/connect-account/disconnect.php';
      wp_die();
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



   /**
    * Useful in conjunction with the hook `woocommerce_admin_field_{$field}` to completely render a custom content in the section.
    *
    * @param array $values
    * @return string
    */
   public static function output_section($values){}

}