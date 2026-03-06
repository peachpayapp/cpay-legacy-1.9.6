<?php
/**
 * Settings Hook Webhooks
 *
 * @author ConvesioPay - based on the Woosa WC integration
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Settings_Hook_Webhooks implements Interface_Hook_Settings_Tab{


   /**
    * The id of the tab.
    *
    * @return string
    */
   public static function id(){
      return 'webhooks';
   }



   /**
    * The name of the tab.
    *
    * @return string
    */
   public static function name(){
      return __('Webhooks', 'convesiopay-woocommerce');
   }



   /**
    * The description of the tab.
    *
    * @return string
    */
   public static function description(){
      return __('Setup Adyen webhooks', 'convesiopay-woocommerce');
   }



   /**
    * The icon URL of the tab.
    *
    * @return string
    */
   public static function icon_url(){
      return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path d="M306 50c41.1 9.5 67.5 49.2 61.1 90.2c-2.1 13.1 6.9 25.4 20 27.4s25.4-6.9 27.4-20C424.8 82 382.6 18.5 316.8 3.3c-68.9-15.9-137.6 27-153.5 95.9c-10.7 46.3 5.2 92.6 37.7 122.7L129.7 336c-.6 0-1.1 0-1.7 0c-26.5 0-48 21.5-48 48s21.5 48 48 48s48-21.5 48-48c0-8.1-2-15.8-5.6-22.5l82.8-132.5c3.4-5.4 4.5-11.9 3-18.1s-5.3-11.6-10.7-15c-28.2-17.6-43.4-51.7-35.5-85.9C220 67 262.9 40.1 306 50zM288 112a16 16 0 1 1 0 32 16 16 0 1 1 0-32zm42.4 38.5c3.6-6.7 5.6-14.4 5.6-22.5c0-26.5-21.5-48-48-48s-48 21.5-48 48s21.5 48 48 48c.6 0 1.1 0 1.7 0l82.8 132.5c3.4 5.4 8.8 9.2 15 10.7s12.7 .3 18.1-3c30.9-19.3 72.1-15.5 99 11.3c31.2 31.2 31.2 81.9 0 113.1c-26.8 26.8-68.1 30.6-99 11.3c-11.2-7-26-3.6-33.1 7.6s-3.6 26 7.6 33.1c49.4 31 115.4 25 158.4-18c50-50 50-131 0-181c-37-37-91-46.6-136.8-28.9L330.4 150.5zM112 384a16 16 0 1 1 32 0 16 16 0 1 1 -32 0zm320 0a16 16 0 1 1 32 0 16 16 0 1 1 -32 0zm64 0c0-26.5-21.5-48-48-48c-17.8 0-33.3 9.7-41.6 24H232c-6.4 0-12.5 2.5-17 7s-7 10.6-7 17c0 36.4-25 69.4-62 77.9c-43.1 9.9-86-16.9-95.9-60c-8.9-38.6 11.7-77.1 47.1-91.8c12.2-5.1 18-19.1 12.9-31.4s-19.1-18-31.4-12.9C22.1 289.4-11 351 3.3 412.8c15.9 68.9 84.6 111.8 153.5 95.9c51-11.8 87.7-52.5 97-100.7H406.4c8.3 14.3 23.8 24 41.6 24c26.5 0 48-21.5 48-48z"/></svg>';
   }



   /**
    * Initiates the hooks.
    *
    * @return void
    */
   public static function init(){

      add_filter(PREFIX . '\module\settings\page\tabs', [__CLASS__, 'add_tab'], 30);
      add_filter(PREFIX . '\module\settings\page\content\fields\\' . self::id(), [__CLASS__, 'add_tab_fields']);

      add_filter(PREFIX . '\module\settings\page\content\fields\\' . self::id(), [__CLASS__, 'add_submit_button'], 99);

      add_action(PREFIX . '\field_generator\render\\' . Util::prefix('step_guide'), [__CLASS__, 'render_step_guide']);
   }



   /**
    * Initiates the tab conditionally.
    *
    * @return void
    */
   public static function maybe_init(){}



   /**
    * Adds the tab in the list.
    *
    * @param array $tabs
    * @return array
    */
   public static function add_tab(array $tabs){

      $tabs[self::id()] = [
         'name'        => self::name(),
         'description' => self::description(),
         'slug'        => self::id(),
         'icon'        => self::icon_url(),
      ];

      return $tabs;
   }



   /**
    * Adds the fields of the tab.
    *
    * @param array $items
    * @return array
    */
   public static function add_tab_fields(array $items){

      $new_items = [
         [
            'type' => 'title',
            'id'   => PREFIX . '_step_guide_start',
         ],
         [
            'id'   => PREFIX . '_step_guide',
            'type' => PREFIX . '_step_guide',
         ],
         [
            'type' => 'sectionend',
            'id'   => PREFIX . '_step_guide_end',
         ],
         [
            'name'     => __('Security', 'convesiopay-woocommerce'),
            'id'   => PREFIX . '_security_start',
            'type' => 'title',
         ],
         [
            'name'     => __('Username', 'convesiopay-woocommerce'),
            'id'       => PREFIX . '_api_username',
            'type'     => 'text',
            'desc_tip' => __('Provide the username you set in authentication section (see step 6.)', 'convesiopay-woocommerce'),
         ],
         [
            'name'     => __('Password', 'convesiopay-woocommerce'),
            'id'       => PREFIX . '_api_password',
            'type'     => 'password',
            'desc_tip' => __('Provide the password you set in authentication section (see step 6.)', 'convesiopay-woocommerce'),
         ],
         [
            'name'     => __('HMAC key', 'convesiopay-woocommerce'),
            'id'       => PREFIX . '_hmac_key',
            'type'     => 'text',
            'desc_tip' => __('Provide HMAC key the from the Customer Area', 'convesiopay-woocommerce'),
         ],
         [
            'type' => 'sectionend',
            'id'   => PREFIX . '_security_end',
         ],
      ];

      $items = array_merge($new_items, $items);

      return $items;
   }



   /**
    * Adds the submit button.
    *
    * @param array $items
    * @return array
    */
   public static function add_submit_button(array $items){

      $items = array_merge($items, [
         [
            'type' => 'title',
            'id'   => PREFIX . '_submit_button',
         ],
         [
            'id'   => PREFIX .'_save_settings',
            'type' => 'submit_button',
         ],
         [
            'type' => 'sectionend',
            'id'   => PREFIX . '_submit_button_end',
         ],
      ]);

      return $items;
   }



   /**
    * Render the output for `step_guide` field type.
    *
    * @return string
    */
   public static function render_step_guide(){

      ?>
      <h2><?php _e('Standard notification', 'convesiopay-woocommerce');?></h2>
      <ol class="mb-25">
         <li><?php printf(__('Log in to your %s account to activate the webhooks', 'convesiopay-woocommerce'), '<a href="https://authn-live.adyen.com/authn/ui/login?request=eyJBdXRoblJlcXVlc3QiOnsiYWN0aXZpdHlHcm91cCI6IkJPX0NBIiwiY3JlZHNSZWFzb24iOlsiTG9nZ2luZyBpbiB0byBhcHBsaWNhdGlvbiBjYSJdLCJmb3JjZU5ld1Nlc3Npb24iOiJmYWxzZSIsImZvcmdvdFBhc3N3b3JkVXJsIjoiaHR0cHM6XC9cL2NhLWxpdmUuYWR5ZW4uY29tXC9jYVwvbG9iYnlcL3Bhc3N3b3JkLXJlc2V0XC9mb3Jnb3QtcGFzc3dvcmQiLCJyZXF1ZXN0VGltZSI6IjIwMjMtMDYtMjBUMTA6MDg6MDYrMDI6MDAiLCJyZXF1ZXN0ZWRDcmVkZW50aWFscyI6W3siUmVxdWVzdGVkQ3JlZGVudGlhbCI6eyJhY2NlcHRlZEFjdGl2aXR5IjpbeyJBY2NlcHRlZEFjdGl2aXR5Ijp7ImFjdGl2aXR5R3JvdXAiOiJCT19DQSIsImFjdGl2aXR5VHlwZSI6IklNUExJQ0lUIiwibWlsbGlzZWNvbmRzQWdvIjo5MDAwMDB9fV0sInR5cGUiOiJQQVNTV09SRCJ9fSx7IlJlcXVlc3RlZENyZWRlbnRpYWwiOnsiYWNjZXB0ZWRBY3Rpdml0eSI6W3siQWNjZXB0ZWRBY3Rpdml0eSI6eyJhY3Rpdml0eUdyb3VwIjoiQk9fQ0EiLCJhY3Rpdml0eVR5cGUiOiJHUkFDRV9DT09LSUUiLCJtaWxsaXNlY29uZHNBZ28iOjB9fV0sInR5cGUiOiJUV09fRkFDVE9SIn19XSwicmVxdWVzdGluZ0FwcCI6ImNhIiwicmV0dXJuVXJsIjoiaHR0cHM6XC9cL2NhLWxpdmUuYWR5ZW4uY29tXC9jYVwvY2FcL292ZXJ2aWV3XC9kZWZhdWx0LnNodG1sIiwic2lnbmF0dXJlIjoiVjAwMlN3ZjYwVVhMUm5KdmkxTmhsdlk2cWdzYTIzK1RLQmU0NlJHd0hkT3NCYU5vPSJ9fQ%3D%3D" target="_blank">Adyen</a>');?></li>
         <li><?php printf(__('Then go to %s', 'convesiopay-woocommerce'), '<b>Developers -> Webhooks</b>');?></li>
         <li><?php printf(__('On the Webhooks page, click %s at the top right of the screen.', 'convesiopay-woocommerce'), '<b>(+) Webhook</b>');?></li>
         <li><?php printf(__('A screen will now appear, under the heading %s click on %s', 'convesiopay-woocommerce'), '<b>Recommended webhooks</b>','<b>Standard notification -> Add</b>');?></li>
         <li>
            <?php printf(__('Below %s, enter the following information:', 'convesiopay-woocommerce'), '<b>Server configuration</b>');?>
            <ul class="<?php echo PREFIX;?>-ullist">
               <li><b>URL</b> - <code><?php echo rest_url('/wc-convesiopay/payment-status');?></code></li>
               <li><b>SSL Version</b> - TLSv12</li>
               <li><b>Active</b> - Checked</li>
               <li><b>Method</b> - HTTP POST</li>
            </ul>
         </li>
         <li><?php printf(__('Below %s, enter a username and password of your choice.', 'convesiopay-woocommerce'), '<b>Authentication</b>');?></li>
         <li><?php printf(__('Click on the %s button', 'convesiopay-woocommerce'), '<b>Save Configuration</b>');?></li>
      </ol>


      <h2><?php _e('Boleto Bancario pending', 'convesiopay-woocommerce');?></h2>
      <ol>
         <li><?php printf(__('Log in to your %s account to activate the webhooks', 'convesiopay-woocommerce'), '<a href="https://authn-live.adyen.com/authn/ui/login?request=eyJBdXRoblJlcXVlc3QiOnsiYWN0aXZpdHlHcm91cCI6IkJPX0NBIiwiY3JlZHNSZWFzb24iOlsiTG9nZ2luZyBpbiB0byBhcHBsaWNhdGlvbiBjYSJdLCJmb3JjZU5ld1Nlc3Npb24iOiJmYWxzZSIsImZvcmdvdFBhc3N3b3JkVXJsIjoiaHR0cHM6XC9cL2NhLWxpdmUuYWR5ZW4uY29tXC9jYVwvbG9iYnlcL3Bhc3N3b3JkLXJlc2V0XC9mb3Jnb3QtcGFzc3dvcmQiLCJyZXF1ZXN0VGltZSI6IjIwMjMtMDYtMjBUMTA6MDg6MDYrMDI6MDAiLCJyZXF1ZXN0ZWRDcmVkZW50aWFscyI6W3siUmVxdWVzdGVkQ3JlZGVudGlhbCI6eyJhY2NlcHRlZEFjdGl2aXR5IjpbeyJBY2NlcHRlZEFjdGl2aXR5Ijp7ImFjdGl2aXR5R3JvdXAiOiJCT19DQSIsImFjdGl2aXR5VHlwZSI6IklNUExJQ0lUIiwibWlsbGlzZWNvbmRzQWdvIjo5MDAwMDB9fV0sInR5cGUiOiJQQVNTV09SRCJ9fSx7IlJlcXVlc3RlZENyZWRlbnRpYWwiOnsiYWNjZXB0ZWRBY3Rpdml0eSI6W3siQWNjZXB0ZWRBY3Rpdml0eSI6eyJhY3Rpdml0eUdyb3VwIjoiQk9fQ0EiLCJhY3Rpdml0eVR5cGUiOiJHUkFDRV9DT09LSUUiLCJtaWxsaXNlY29uZHNBZ28iOjB9fV0sInR5cGUiOiJUV09fRkFDVE9SIn19XSwicmVxdWVzdGluZ0FwcCI6ImNhIiwicmV0dXJuVXJsIjoiaHR0cHM6XC9cL2NhLWxpdmUuYWR5ZW4uY29tXC9jYVwvY2FcL292ZXJ2aWV3XC9kZWZhdWx0LnNodG1sIiwic2lnbmF0dXJlIjoiVjAwMlN3ZjYwVVhMUm5KdmkxTmhsdlk2cWdzYTIzK1RLQmU0NlJHd0hkT3NCYU5vPSJ9fQ%3D%3D" target="_blank">Adyen</a>');?></li>
         <li><?php printf(__('Go to %s', 'convesiopay-woocommerce'), '<b>Developers -> Webhooks</b>');?></li>
         <li><?php printf(__('On the Webhooks page, click %s at the top right of the screen.', 'convesiopay-woocommerce'), '<b>(+) Webhook</b>');?></li>
         <li><?php printf(__('A screen will now appear, under the heading %s click on %s', 'convesiopay-woocommerce'), '<b>Recommended webhooks</b>','<b>Boleto Bancario Pending Notification -> Add</b>');?></li>
         <li>
            <?php printf(__('Below %s, enter the following information:', 'convesiopay-woocommerce'), '<b>Server configuration</b>');?>
            <ul class="<?php echo PREFIX;?>-ullist">
               <li><b>URL</b> - <code><?php echo rest_url('/wc-convesiopay/boleto-payment-status');?></code></li>
               <li><b>SSL Version</b> - TLSv12</li>
               <li><b>Active</b> - Checked</li>
               <li><b>Method</b> - HTTP POST</li>
            </ul>
         </li>
         <li><?php printf(__('Below %s, enter a username and password of your choice.', 'convesiopay-woocommerce'), '<b>Authentication</b>');?></li>
         <li><?php printf(__('Click on the %s button', 'convesiopay-woocommerce'), '<b>Save Configuration</b>');?></li>
      </ol>
      <?php
   }

}