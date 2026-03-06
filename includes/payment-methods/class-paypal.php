<?php
/**
 * Paypal
 *
 * Payment type     : PayPal
 * Payment flow     : Direct (Web)
 * Countries        : Any
 * Currencies       : AUD, BRL, CAD, CHF, CZK, DKK, EUR, GBP, HKD, HUF, ILS, INR, JPY, MXN, MYR, NOK, NZD, PHP, PLN, RUB, SEK, SGD, THB, TWD, USD
 * Recurring        : Yes
 * Refunds          : Yes
 * Partial refunds  : Yes
 * Separate captures: Yes
 * Partial captures : Yes
 * Chargebacks      : Yes
 *
 * @author ConvesioPay - based on the Woosa WC integration
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
use Automattic\WooCommerce\StoreApi\Utilities\NoticeHandler;

defined( 'ABSPATH' ) || exit;


class Paypal extends Abstract_Gateway{


   /**
    * List of countries where is available.
    *
    * @since 1.1.0
    * @return array
    */
    public function available_countries(){

      return [
         '_ANY_' => [
            'currencies' => ['AUD', 'BRL', 'CAD', 'CHF', 'CZK', 'DKK', 'EUR', 'GBP', 'HKD', 'HUF', 'ILS', 'INR', 'JPY', 'MXN', 'MYR', 'NOK', 'NZD', 'PHP', 'PLN', 'RUB', 'SEK', 'SGD', 'THB', 'TWD', 'USD'],
         ],
      ];
   }



   /**
    * Gets default payment method title.
    *
    * @since 1.1.0
    * @return string
    */
   public function get_default_title(){
      return __('ConvesioPay - Paypal', 'convesiopay-woocommerce');
   }



   /**
    * Gets default payment method description.
    *
    * @since 1.1.0
    * @return string
    */
   public function get_default_description(){
      return $this->show_supported_country();
   }



   /**
    * Gets default description set in settings.
    *
    * @since 1.1.0
    * @return string
    */
   public function get_settings_description(){}



   /**
    * Type of the payment method (e.g ideal, scheme. bcmc).
    *
    * @since 1.1.0
    * @return string
    */
   public function payment_method_type(){
      return 'paypal';
   }



   /**
    * Returns the payment method to be used for recurring payments
    *
    * @since 1.1.0
    * @return string
    */
   public function recurring_payment_method(){
      return $this->payment_method_type();
   }



   /**
    * Adds an array of fields to be displayed on the gateway's settings screen.
    *
    * @since 1.1.0
    * @return void
    */
   public function init_form_fields() {

      $is_enabled = parent::init_form_fields();

      if( $is_enabled === false ) return;

      if('paypal' === $this->payment_method_type()){
         $this->form_fields = array_merge($this->form_fields, array(
            'config_desc'    => array(
               'title'       => __('Test mode', 'convesiopay-woocommerce'),
               'label'       => __('Enable/Disable', 'convesiopay-woocommerce'),
               'default' => 'yes',
               'type'        => 'config_desc',
            ),
         ));
      }
   }



   /**
    * Generates the HTML for `config_desc` field type
    *
    * @since 1.1.0
    * @return string
    */
   public function generate_config_desc_html(){

      ob_start();
      ?>
      <tr valign="top">
			<td colspan="2" class="forminp" style="padding: 0;">
            <h3><?php _e('Configure PayPal API permissions', 'convesiopay-woocommerce');?></h3>
            <p><?php _e("To connect your PayPal account with your Adyen integration you need to grant permission to Adyen's API to integrate with your PayPal account.", 'woosa0-adyen');?></p>
            <ol>
               <li>
                  <p><?php printf(__("Follow %sPayPal's instructions on granting third party permissions%s", 'convesiopay-woocommerce'), '<a href="https://developer.paypal.com/docs/classic/admin/third-party" target="_blank">', '</a>');?></p>
               </li>
               <li>
                  <p><?php printf(__('Under %s, depending on your account type, enter:', 'convesiopay-woocommerce'), '<b>Third Party Permission Username</b>');?></p>
                  <ul style="list-style: disc;padding-left: 20px;">
                     <li><b>Live:</b> <?php _e('Enter', 'convesiopay-woocommerce');?> <code>paypal_api2.adyen.com</code></li>
                     <li><b>Test:</b> <?php _e('Enter', 'convesiopay-woocommerce');?> <code>sell1_1287491142_biz_api1.adyen.com</code></li>
                  </ul>
               </li>
               <li>
                  <p><?php printf(__('In the %s list, select the following boxes: ', 'convesiopay-woocommerce'), '<b>Available Permissions</b>');?></p>
                  <ul style="list-style: disc;padding-left: 20px;">
                     <li><b>Use Express Checkout to process payments.</b></li>
                     <li><b>Issue a refund for a specific transaction.</b></li>
                     <li><b>Process your shopper's credit or debit card payments.</b></li>
                     <li><b>Authorize and capture your PayPal transactions.</b></li>
                     <li><b>Obtain information about a single transaction.</b></li>
                     <li><b>Obtain authorization for pre-approved payments and initiate pre-approved transactions.</b></li>
                     <li><b>Generate consolidated reports for all accounts.</b> (if available in your region)</li>
                     <li><b>Use Express Checkout to process mobile payments.</b> (if you plan on supporting mobile payments)</li>
                     <li><b>Charge an existing customer based on a prior transaction.</b></li>
                     <li><b>Create and manage Recurring Payments.</b></li>
                     <li><b>Obtain your PayPal account balance.</b></li>
                     <li><b>Initiate transactions to multiple recipients in a single batch.</b></li>
                  </ul>
               </li>
               <li>
                  <p><?php printf(__('Click %s.', 'convesiopay-woocommerce'), '<b>Add</b>');?></p>
               </li>
            </ol>
			</td>
		</tr>
      <?php

      return ob_get_clean();
   }



   /**
    * Validates extra added fields.
    *
    * @since 1.1.0
    * @return bool
    */
   public function validate_fields() {
      return Abstract_Gateway::validate_fields();
   }



   /**
    * Builds the required payment payload
    *
    * @since 1.1.0
    * @param \WC_Order $order
    * @param string $reference
    * @return array
    */
   protected function build_payment_payload(\WC_Order $order, $reference){
      return parent::build_payment_payload($order, $reference);
   }



   /**
    * Processes the payment.
    *
    * @since 1.1.0
    * @param int $order_id
    * @return array
    */
   public function process_payment($order_id) {

      parent::process_payment($order_id);

      $order     = wc_get_order($order_id);
      $reference = $order_id;
      $payload   = $this->build_payment_payload( $order, $reference );

      $response = $this->api->checkout()->send_payment($payload);

      if($response->status == 200){

         return $this->process_payment_result( $response, $order );

      }else{

         wc_add_notice($response->body->message, 'error');

         NoticeHandler::convert_notices_to_exceptions( 'woocommerce_rest_payment_error' );

      }

      return ['result' => 'failure'];
   }



   /**
    * Processes the payment result.
    *
    * @since 1.2.0
    * @param object $response
    * @param \WC_Order $order
    * @return array
    */
   protected function process_payment_result( $response, $order ){

      $body        = Util::obj_to_arr($response->body);
      $result_code = Util::array($body)->get('resultCode');
      $action      = Util::array($body)->get('action');

      $result = [
         'result'   => 'success',
         'redirect' => Service_Util::get_return_page_url($order, $result_code)
      ];

      $order->update_meta_data('_'.PREFIX.'_payment_resultCode', $result_code);
      $order->update_meta_data('_'.PREFIX.'_payment_action', $action);
      $order->save();

      if( 'RedirectShopper' == $result_code ){

         //redirect to process payment action via Web Component
         $result = [
            'result'   => 'success',
            'redirect' => add_query_arg(
               [
                  PREFIX . '_payment_method' => $this->payment_method_type(),
                  PREFIX . '_order_id'       => $order->get_id(),
               ],
               Service_Util::get_checkout_url($order)
            )
         ];

      }

      return $result;

   }


}