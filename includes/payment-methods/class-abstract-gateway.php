<?php
/**
 * WooCommerce Payments
 *
 * This abstract class is used to extends WooCommerce Payments by different Adyen payment methods.
 *
 * @author ConvesioPay - based on the Woosa WC integration
 * @since 1.0.0
 */

namespace Woosa\Adyen;

use VIISON\AddressSplitter\AddressSplitter;



//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;

if(!class_exists('\WC_Payment_Gateway')){
   include_once WP_CONTENT_DIR . '/plugins/woocommerce/includes/abstracts/abstract-wc-settings-api.php';
   include_once WP_CONTENT_DIR . '/plugins/woocommerce/includes/abstracts/abstract-wc-payment-gateway.php';
}

abstract class Abstract_Gateway extends \WC_Payment_Gateway{

	/**
	 * A Service instance used to interact with the Adyen API.
	 *
	 * @var Service
	 */
	public $api;

	/**
	 * Whether or not the payment method is in test mode.
	 *
	 * @var bool
	 */
	public $testmode;

   /**
    * Whether or not the payment information was displayed
    *
    * @since 1.0.0
	 * @var bool
	 */
	public static $payment_info_displayed = false;


   /**
    * Whether or not the payment method is active in ConvesioPay account.
    *
    * @since 1.0.0
	 * @var bool
	 */
	public $is_activated = null;



   /**
    * List of available currencies.
    *
    * @var array
    */
   public $currencies = [];


   /**
    * Constructor of this class.
    *
    * @param bool $init_hooks
    * @since 1.0.0
    */
    public function __construct($init_hooks = true){

      $this->id                 = strtolower(preg_replace('/[^A-Za-z0-9\-]/', '_', static::class));
      $this->enabled            = 'no';
      $this->method_title       = $this->get_default_title();
      $this->method_description = $this->get_default_description();
      $this->api                = new Service;
      $this->testmode           = $this->api->is_test_mode();
      $this->icon               = $this->get_icon_url();
      $this->title              = $this->get_option('title', $this->get_default_title());
      $this->description        = $this->get_option('description');
      $this->is_activated       = Service_Util::is_payment_method_active( $this->payment_method_type() );
      $this->supports = [
         'products',
         'refunds',
      ];

      // Load the settings.
      $this->init_form_fields();
      $this->init_settings();

      if ($init_hooks) {

         add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
         add_action('woocommerce_scheduled_subscription_payment_'.$this->id, [$this, 'renewal_subscription'], 10, 2);
         add_action('woocommerce_thankyou', [$this, 'received_order_page'], 1);
         add_action('woocommerce_thankyou', [$this, 'send_payment_details']);
         add_action('before_woocommerce_pay', [$this, 'pay_order_page'], 1);

         add_action('woocommerce_pay_order_after_submit', [$this, 'display_payment_action']);
         add_action('woocommerce_after_checkout_form', [$this, 'display_payment_action']);
         add_action('woocommerce_thankyou_' . $this->id, [$this, 'display_payment_action']);

      }

    }



   /**
    * List of countries where the payment method is available only.
    *
    * @since 1.1.0
    * @return array
    */
   public function available_countries(){

      return [];
   }



   /**
    * Displays supported countries and currencies.
    *
    * @since 1.1.0
    * @return string
    */
   public function show_supported_country(){

      $countries = [];

      foreach($this->available_countries() as $country_code => $data){

         $country_code = '_ANY_' === $country_code ? __('ANY', 'convesiopay-woocommerce') : $country_code;

         if( empty(Util::array($data)->get('currencies')) ){
            $countries[] = $country_code;
         }else{
            $currencies = Util::array($data)->get('currencies');
            $countries[] = $country_code . ' ('.implode(', ', $currencies).')';
         }
      }

      $result = empty($countries) ? sprintf(__('%sSupported country:%s ANY', 'convesiopay-woocommerce'), '<b>', '</b>') : sprintf(__('%sSupported country:%s %s', 'convesiopay-woocommerce'), '<b>', '</b>', implode(', ', $countries));

      return $result;
   }



   /**
    * Gets default payment method title.
    *
    * @since 1.0.0
    * @return string
    */
   abstract public function get_default_title();



   /**
    * Gets default payment method description.
    *
    * @since 1.0.0
    * @return string
    */
   abstract public function get_default_description();



   /**
    * Gets default description set in settings.
    *
    * @since 1.0.0
    * @return string
    */
   abstract public function get_settings_description();



   /**
    * Type of the payment method (e.g ideal, scheme. bcmc).
    *
    * @since 1.0.0
    * @return string
    */
   abstract public function payment_method_type();



   /**
    * Returns the payment method to be used for recurring payments
    *
    * @since 1.0.0
    * @return string
    */
   abstract public function recurring_payment_method();



   /**
    * Gets payment method icon by method type.
    *
    * @since 1.0.0
    * @return string
    */
   public function get_icon_url(){

      $url = $this->get_option('icon_url');
      $url = empty($url) ? untrailingslashit(plugin_dir_url(__FILE__)) . '/assets/images/'.$this->payment_method_type().'.svg' : $url;

      return apply_filters( PREFIX.'_'.$this->id . '_icon_url', $url );
   }



   /**
	 * Checks if the gateway is available for use.
	 *
    * @since 1.1.1 - check if there is a valid origin key
    * @since 1.1.0 - check if it's available based on country and currencies
    * @since 1.0.3 - add currency verification
    * @since 1.0.0
	 * @return bool
	 */
	public function is_available() {

      if( ! $this->api->is_configured() ) return false;

      if(empty(Service_Util::get_origin_key())){
         return false;
      }

      //only in WooCommerce checkout
      if( WC()->cart && $this->get_order_total() > 0 ) {

         if( ! empty($this->available_countries()) ){

            $customer_country = WC()->customer->get_billing_country();
            $any_country = Util::array($this->available_countries())->get('_ANY_');
            $country = Util::array($this->available_countries())->get($customer_country);

            if( empty($country) && empty($any_country) ){

               return false;

            }else{

               $currencies = empty($any_country) ? $country['currencies'] : $any_country['currencies'];

               if( ! empty($currencies) && ! in_array(get_woocommerce_currency(), $currencies)){
                  return false;
               }
            }
         }
      }

      return parent::is_available();
   }



	/**
	 * Return whether or not this gateway still requires setup to function.
	 *
	 * When this gateway is toggled on via AJAX, if this returns true a
	 * redirect will occur to the settings page instead.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function needs_setup() {

      if( ! $this->is_activated ){
         return true;
      }

		return false;
	}



   /**
	 * Gets the transaction URL.
	 *
    * @since 1.0.0
	 * @param  WC_Order $order Order object.
	 * @return string
	 */
	public function get_transaction_url( $order ) {

      if ( $this->testmode ) {
         $this->view_transaction_url = 'https://dev.convesiopay.com/payments/%s';
      } else {
         $this->view_transaction_url = 'https://convesiopay.com/payments/%s';
      }

		return parent::get_transaction_url( $order );
   }



   /**
    * Gets the base URL of Adyen platform
    *
    * @since 1.0.0
    * @return string
    */
   public function get_service_base_url(){

      if ( $this->testmode ) {
			return 'https://ca-test.adyen.com';
      }

      return 'https://ca-live.adyen.com';
   }



   /**
    * Gets details of a given method type.
    *
    * @since 1.0.0
    * @return array
    */
   public function get_payment_method_details(){

      $method = [];

      foreach($this->api->checkout()->get_payment_methods() as $method){
         if(Util::array($method)->get('type') == $this->payment_method_type()){
            return $method;
         }
      }

      return $method;

   }



   /**
    * Checks if a given payment method is enabled in WooCommerce
    *
    * @since 1.0.0
    * @param string $method_id
    * @return boolean
    */
   public function is_payment_method_enabled($method_id){

      $method_settings = get_option("woocommerce_{$method_id}_settings", []);

      if( Util::array($method_settings)->get('enabled') === 'yes' ) return true;

      return false;
   }



   /**
    * Checks whether or not SEPA Direct Debit is enabled then this could support recurring payments
    *
    * @since 1.0.0
    * @return bool
    */
   public function support_recurring(){

      if( $this->is_payment_method_enabled('woosa_adyen_sepa_direct_debit') ) return true;

      return false;
   }



   /**
    * Validates extra added fields.
    *
    * @since 1.0.0
    * @return bool
    */
   public function validate_fields() {
      return parent::validate_fields();
   }



   /**
    * Gets subscriptions for an order
    *
    * @since 1.0.3
    * @param int|string $order_id
    * @return array
    */
   public function get_subscriptions_for_order($order_id){

      if(function_exists('\wcs_get_subscriptions_for_order')){
         return wcs_get_subscriptions_for_order( $order_id );
      }

      return [];
   }



   /**
    * Gets subscriptions for an renewal order
    *
    * @since 1.0.3
    * @param WC_Order $order
    * @return array
    */
   public function get_subscriptions_for_renewal_order($order){

      if(function_exists('\wcs_get_subscriptions_for_renewal_order')){
         return wcs_get_subscriptions_for_renewal_order( $order );
      }

      return [];
   }



   /**
    * Sends the payment when a WC Subscription is renewed.
    *
    * @since 1.3.0 - use local recurring reference
    * @since 1.2.1 - retrieve the recurring reference using always the parent order
    * @since 1.1.0 - use function `build_payment_payload` to have the common data included
    *              - replace `_#subscription#_` with `-S`
    * @since 1.0.10- added `recurringProcessingModel` set on `Subscription`
    * @since 1.0.7 - use \WC_Subscription instance to manipulate the metadata
    *              - use the shopper reference from the metadata
    * @since 1.0.0
    * @param float $amount
    * @param \WC_Order $order
    * @return void
    */
   public function renewal_subscription($amount, $order){

      $subscriptions = $this->get_subscriptions_for_renewal_order( $order );

      foreach($subscriptions as $sub_id => $subscription){

         // Add the shopper reference to the order
         $shopper_reference = apply_filters(PREFIX . '\renewal_subscription\recurring_reference', $subscription->get_meta('_' . PREFIX . '_shopper_reference'));
         $order->update_meta_data('_'.PREFIX.'_shopper_reference', $shopper_reference);
         $order->save();

         $persisted_recurring_reference = apply_filters(PREFIX . '\renewal_subscription\recurring_reference', $subscription->get_meta('_' . PREFIX . '_recurringDetailReference'));
         // Check for both empty and invalid (whitespace-only) tokens so we fetch from API when needed
         $token_empty_or_invalid = ('' === $persisted_recurring_reference || (is_string($persisted_recurring_reference) && trim($persisted_recurring_reference) === ''));
         if ($token_empty_or_invalid) {
            $recurring_reference = $this->get_renewal_payment_reference($shopper_reference);
            if (!empty($recurring_reference)) {
               $subscription->update_meta_data('_' . PREFIX . '_recurringDetailReference', $recurring_reference);
               $subscription->update_meta_data('_cpay_recurringDetailReference', $recurring_reference);
               $subscription->update_meta_data('_adn_recurringDetailReference', $recurring_reference);
               $subscription->save();
            }
         } else {
            $recurring_reference = is_string($persisted_recurring_reference) ? trim($persisted_recurring_reference) : $persisted_recurring_reference;
         }

         if(empty($recurring_reference)){

            Util::wc_error_log([
               'TITLE' => '====== RENEWAL SUBSCRIPTION ERROR ======',
               'MESSAGE' => 'The recurring reference is not found',
               'DATA' => [
                  'subscription_id' => $sub_id,
                  'order_id' => $order->get_id(),
               ]
            ]);

            $order->set_status('failed');
            $order->add_order_note(__('We could not found a valid recurring reference.', 'convesiopay-woocommerce'));
            $order->save();

         }else{

            $reference = "{$order->get_id()}-S{$sub_id}";
            $payload = apply_filters(PREFIX . '\renewal_subscription\payload', array_merge($this->build_payment_payload($order, $reference), [
               'amount' => [
                  'currency' => get_woocommerce_currency(),
                  'value' => Service_Util::format_amount($amount)
               ],
               'paymentMethod' => [
                  'type' => $this->recurring_payment_method(),
                  'storedPaymentMethodId' => $recurring_reference
               ],
               'shopperInteraction' => 'ContAuth',
               'recurringProcessingModel' => 'Subscription',
            ]));

            $this->api->checkout()->send_payment($payload);
         }

      }

   }



   /**
    * Processes the payment.
    *
    * @since 1.0.9 - use order instance to save the shopper reference
    * @since 1.0.0
    * @param int $order_id
    * @return array
    */
   public function process_payment($order_id) {

      $order = wc_get_order($order_id);
      $order->update_meta_data('_'.PREFIX.'_shopper_reference', Service_Util::get_shopper_reference());
      $order->save();
   }



	/**
	 * Processes a refund.
	 *
    * @since 1.1.0 - show error if payment reference is empty
    * @since 1.0.0
	 * @param  int    $order_id Order ID.
	 * @param  float  $amount Refund amount.
	 * @param  string $reason Refund reason.
	 * @return boolean True or false based on success, or a WP_Error object.
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {

      $order            = wc_get_order($order_id);
      $reference        = $order->get_meta('_'.PREFIX.'_payment_pspReference');
      $payment_captured = $order->get_meta('_'.PREFIX.'_payment_captured');
      $payment_method   = $order->get_payment_method();


      if($payment_method === 'woosa_adyen_boleto'){
         return new \WP_Error( 'broke', sprintf(
            __( 'Sorry you cannot refund payments captured via %s', 'convesiopay-woocommerce'),
            $order->get_payment_method_title()
         ));
      }


      if( empty($reference) ){
         return new \WP_Error( 'broke', __( 'Sorry you cannot refund this because the payment reference is invalid. Please try to refund it manually from ConvesioPay account.', 'convesiopay-woocommerce'));
      }


      if($payment_captured === 'yes'){

         if($payment_method === 'woosa_adyen_sepa_direct_debit' && ! apply_filters(PREFIX . '\process_refund\allow_sepa_direct_debit_refunds_after_capture', false)){
            return new \WP_Error( 'broke', sprintf(
               __( 'Sorry we cannot refund payments via %s. You have to do it manually from your ConvesioPay account.', 'convesiopay-woocommerce'),
               $order->get_payment_method_title()
            ));
         }

         $response = $this->api->checkout()->refund_payment( $reference, $amount );

      }else{

         if($amount == $order->get_total()){

            $response = $this->api->checkout()->cancel_payment($reference);

         }else{

            return new \WP_Error( 'broke',
               __( 'Sorry, you cannot refund a partial amount because the transaction has not been captured yet but only cancel the entire payment', 'convesiopay-woocommerce')
            );
         }
      }

      // Check if refund was successful - HTTP status 200/201 and response body status is "received"
      if(($response->status == 200 || $response->status == 201) && isset($response->body->status) && $response->body->status === 'received'){

         $body = Util::obj_to_arr($response->body);

         $order->read_meta_data();
         $order_psp_reference = $order->get_meta('_'.PREFIX.'_payment_pspReference');

         if ($order_psp_reference !== $body['pspReference']) {

            $order->update_meta_data('_'.PREFIX.'_refund_pspReference', $body['pspReference']);

         }

         return true;

      }else{

         $order->add_order_note(sprintf(__('The refund did not succeed. Request response: %s', 'convesiopay-woocommerce'),  json_encode($response->body)));

      }

      $order->save();

		return false;
	}



   /**
    * Adds an array of fields to be displayed on the gateway's settings screen.
    *
    * @since 1.0.7 - use `get_settings_description` instead of `get_default_description`
    * @since 1.0.0
    * @return void
    */
   public function init_form_fields() {

      if( ! $this->is_activated ){

         $this->form_fields = array(
            'show_notice' => array(
               'type' => 'show_notice',
            ),
         );

      }else{

         $this->form_fields = array(
            'enabled'     => array(
               'title'    => __('Enable/Disable', 'convesiopay-woocommerce'),
               'type'     => 'checkbox',
               'label'    => sprintf(__('Enable %s', 'convesiopay-woocommerce'), $this->get_default_title()),
               'default'  => 'no'
            ),
            'title'       => array(
               'title'    => __('Title', 'convesiopay-woocommerce'),
               'type'     => 'text',
               'desc_tip' => __('The title which the user sees during checkout.', 'convesiopay-woocommerce' ),
               'default'  => explode( 'ConvesioPay - ', $this->get_default_title() )[1],
            ),
            'description' => array(
               'title'    => __('Description', 'convesiopay-woocommerce'),
               'type'     => 'text',
               'desc_tip' => __('The description which the user sees during checkout.', 'convesiopay-woocommerce'),
               'default'  => $this->get_settings_description(),
            ),
            'icon_url'    => array(
               'title'    => __('Icon URL', 'convesiopay-woocommerce'),
               'type'     => 'url',
               'desc_tip' => __('The URL of the payment icon. Leave empty to use the default.', 'convesiopay-woocommerce'),
            )
         );
      }
   }



   /**
    * Generates the HTML for `show_notice` field type
    *
    * @since 1.0.0
    * @return string
    */
   public function generate_show_notice_html(){

      ob_start();
      ?>
      <tr valign="top">
			<td colspan="2" class="forminp" style="padding: 0;">
            <p>
               <?php printf(
                  __('This payment method is not enabled in your ConvesioPay account. %sGo to my account.%s', 'convesiopay-woocommerce'),
                  '<a href="'.$this->get_service_base_url().'" target="_blank">',
                  '</a>'
               );?>
            </p>
            <span>
               <?php printf(
               __('%sNote:%s Please make sure you have removed the cache. %sGo to settings.%s', 'convesiopay-woocommerce'),
               '<b>',
               '</b>',
               '<a href="'.SETTINGS_URL.'&section=tools">',
               '</a>'
               );?>
               </span>
			</td>
		</tr>
      <?php

      return ob_get_clean();
   }



   /**
    * Displays payment information on thank you page.
    *
    * @since 1.2.0 - change function name
    * @since 1.0.0
    * @param int $order_id
    * @return string|void
    */
   public function received_order_page($order_id){

      $order = wc_get_order($order_id);
      $info = sprintf(__('Order completed using %s', 'convesiopay-woocommerce'), $order->get_payment_method_title());

      if( ! self::$payment_info_displayed && $order->get_payment_method() === $this->id){
         echo '<section class="woocommerce-info" >'.wptexturize( esc_html($info) ).'</section>';

         self::$payment_info_displayed = true;
      }

      //collect payload if any
      if(isset($_GET['payload'])){
         $order->update_meta_data('_' . PREFIX . '_payment_payload', Util::array($_GET)->get('payload'));
         $order->save();
      }

      //collect redirect result if any
      if(isset($_GET['redirectResult'])){
         $order->update_meta_data('_' . PREFIX . '_payment_redirectResult', Util::array($_GET)->get('redirectResult'));
         $order->save();
      }
   }



   /**
    * Displays payment information on pay order page.
    *
    * @return string|void
    */
   public function pay_order_page(){

      $order_id = wc_get_order_id_by_order_key(Util::array($_GET)->get('key'));
      $order    = wc_get_order($order_id);

      if($order instanceof \WC_Order){

         if( ! self::$payment_info_displayed && $order->get_payment_method() === $this->id && 'failed' === Util::array($_GET)->get('payment_result')){

            $info = __('Your payment was not successful. Please complete your order with a different payment method.', 'convesiopay-woocommerce');

            echo '<section class="woocommerce-info" >'.wptexturize( esc_html($info) ).'</section>';

            self::$payment_info_displayed = true;
         }

      }
   }



   /**
    * Create a list with the order items which will be used in API request
    *
    * @since 1.1.0
    * @param \WC_Order $order
    * @return void
    */
   public function list_order_items(\WC_Order $order){

      $list_items = [];

      foreach($order->get_items() as $item){

         $tax_percentage = 0;
         $price_excl_tax = floatval( $item->get_total() );
         $tax_amount     = $item->get_total_tax();
         $product        = $item->get_product();
         $price_incl_tax = $price_excl_tax + $tax_amount;

         if ( is_float( $price_excl_tax ) && $price_excl_tax > 0 ) {
            $tax_percentage = $tax_amount * 100 / $price_excl_tax;
         }

         $list_items[] = [
            'id'                 => $product->get_id(),
            'quantity'           => $item->get_quantity(),
            'amountIncludingTax' => Service_Util::format_amount($price_incl_tax),
            'amountExcludingTax' => Service_Util::format_amount($price_excl_tax),
            'taxAmount'          => Service_Util::format_amount($tax_amount),
            'taxPercentage'      => Service_Util::format_amount($tax_percentage),
            'description'        => $product->get_name(),
            'productUrl'         => get_permalink($product->get_id()),
            'imageUrl'           => wp_get_attachment_url($product->get_image_id()),
         ];
      }

      return $list_items;
   }



   /**
    * Builds the required payment payload
    *
    * @since 1.1.3 - add by default `shopperInteraction` and `recurringProcessingModel`
    * @since 1.1.1- fix wrong variable name
    * @since 1.1.0
    * @param \WC_Order $order
    * @param string $reference
    * @return array
    */
   protected function build_payment_payload(\WC_Order $order, $reference){

      $payload = apply_filters(PREFIX . '\abstract_gateway\payment_payload', [
         'channel'                  => 'web',
         'origin'                   => home_url(),
         'reference'                => get_option(PREFIX . '_convesiopay_api_key') . '$' . Order::add_reference_prefix($reference),
         'returnUrl'                => $this->get_return_url( $order ),
         'merchantAccount'          => $this->api->get_merchant(),
         'countryCode'              => $order->get_billing_country(),
         'telephoneNumber'          => $order->get_billing_phone(),
         'lineItems'                => $this->list_order_items($order),
         'recurringProcessingModel' => Checkout::has_subscription() ? 'Subscription' : 'CardOnFile',
         'shopperInteraction'       => 'Ecommerce',
         'shopperIP'                => Core::get_client_ip(),
         'shopperLocale'            => $this->get_locale(),
         'shopperEmail'             => $order->get_billing_email(),
         'shopperReference'         => $order->get_meta('_'.PREFIX.'_shopper_reference', true),
         'shopperName' => [
            'firstName' => $order->get_billing_first_name(),
            'lastName'  => $order->get_billing_last_name(),
         ],
         'amount' => [
            "currency" => get_woocommerce_currency(),
            "value" => Service_Util::format_amount( $this->get_order_total() )
         ],
         'paymentMethod' => [
            'type' => $this->payment_method_type(),
         ],
         'billingAddress' => [
            'city'              => $order->get_billing_city(),
            'country'           => $order->get_billing_country(),
            'postalCode'        => str_replace('-', '', $order->get_billing_postcode()),
            'stateOrProvince'   => $order->get_billing_state(),
         ],
         'deliveryAddress' => [
            'city'              => empty($order->get_shipping_city()) ? $order->get_billing_city() : $order->get_shipping_city(),
            'country'           => empty($order->get_shipping_country()) ? $order->get_billing_country() : $order->get_shipping_country(),
            'postalCode'        => empty($order->get_shipping_postcode()) ? str_replace('-', '', $order->get_billing_postcode()) : str_replace('-', '', $order->get_shipping_postcode()),
            'stateOrProvince'   => empty($order->get_shipping_state()) ? $order->get_billing_state() : $order->get_shipping_state(),
         ],
         'browserInfo' => [
            'userAgent'      => Util::array($_SERVER)->get('HTTP_USER_AGENT'),
            'acceptHeader'   => Util::array($_SERVER)->get('HTTP_ACCEPT'),
            'language'       => Core::get_locale(),
            'javaEnabled'    => true,
            'colorDepth'     => 24,
            'timeZoneOffset' => 0,
            'screenHeight'   => 723,
            'screenWidth'    => 1536
         ],
      ]);


      try{

         $b_address = AddressSplitter::splitAddress( $order->get_billing_address_1() );

         $payload['billingAddress']['street'] = Util::array($b_address)->get('streetName');
         $payload['billingAddress']['houseNumberOrName'] = Util::array($b_address)->get('houseNumber');

      }catch(\Exception $e){

         $payload['billingAddress']['street'] = $order->get_billing_address_1();
         $payload['billingAddress']['houseNumberOrName'] = $order->get_billing_address_2();
      }


      try{

         $s_address = AddressSplitter::splitAddress( $order->get_shipping_address_1() );

         $payload['deliveryAddress']['street'] = Util::array($s_address)->get('streetName');
         $payload['deliveryAddress']['houseNumberOrName'] = Util::array($s_address)->get('houseNumber');

      }catch(\Exception $e){

         $payload['deliveryAddress']['street'] = empty($order->get_shipping_address_1()) ? $order->get_billing_address_1() : $order->get_shipping_address_1();
         $payload['deliveryAddress']['houseNumberOrName'] = empty($order->get_shipping_address_2()) ? $order->get_billing_address_2() : $order->get_shipping_address_2();
      }


      $store = $this->get_store($order);

      if (!empty($store)) {

         $payload['store'] = $store;

      }

      return $payload;
   }



   /**
    * Get the store for order
    *
    * @param \WC_Order $order
    * @return string
    */
   public function get_store($order) {

      $store          = '';
      $user_country   = '';
      $country_source = Option::get('country_source', 'billing_country');

      switch ($country_source) {
         case 'billing_country':
            $user_country = $order->get_billing_country();
            break;
         case 'shipping_country':
            $user_country = $order->get_shipping_country();
            break;
      }

      if (!empty($user_country)) {

         $stores_mapping = Option::get('stores_mapping', []);
         $user_country   = strtoupper($user_country);

         if (!empty($stores_mapping[$user_country])) {
            $store = $this->get_store_reference_by_id($stores_mapping[$user_country]);
         }
      }

      return $store;
   }



   /**
    * Get store reference by ID
    *
    * @param $store_id
    * @return string
    */
   public function get_store_reference_by_id($store_id) {

      $stores = Service::management()->get_stores();

      if (!empty($stores)) {

         foreach ($stores as $store) {

            $store_data = Util::obj_to_arr($store);

            if ($store_id === Util::array($store_data)->get('id')) {
               return Util::array($store_data)->get('reference', $store_id);
            }

         }

      }

      return '';
   }



   /**
    * Sends received payment details to be processed
    *
    * @since 1.1.3 - add support for API Checkout v67
    * @since 1.0.0
    * @return void
    */
   public function send_payment_details(){

      if(is_checkout() && isset($_GET['key']) && (isset($_GET['redirectResult']) || isset($_GET['payload']))){

         $order_id = wc_get_order_id_by_order_key(Util::array($_GET)->get('key'));
         $order    = wc_get_order($order_id);

         if($order instanceof \WC_Order){

            //only if matches the our payment methods
            if( $order->get_payment_method() === $this->id ){

               if(isset($_GET['redirectResult'])){

                  $payload = [
                     'details' => [
                        'redirectResult' => urldecode(Util::array($_GET)->get('redirectResult')),
                     ]
                  ];

                  $response = $this->api->checkout()->send_payment_details($payload);

                  Order::payment_result($order, $response);

               /**
                * this is a temporary fix for payment methods like PayPal
                * @since 1.3.2 - simulate the request response
                * @since 1.2.0
                */
               }elseif(isset($_GET['payload']) && isset($_GET['resultCode'])){
                  Order::payment_result($order, (object)[
                     'status' => 200,
                     'body' => (object)[
                        'resultCode' => Util::array($_GET)->get('resultCode')
                     ]
                  ]);
               }
            }
         }
      }

   }



   /**
    * Displays the payment action in a popup.
    *
    * @since 1.3.0
    * @return string
    */
   public function display_payment_action(){

      if(isset($_GET[PREFIX.'_payment_method'])){

         $order_id = get_query_var('order-pay', get_query_var('order-received', Util::array($_GET)->get('order_id')));
         $order    = wc_get_order($order_id);

         if( ! $order instanceof \WC_Order ){
            return;
         }

         $payment_method = Util::array($_GET)->get(PREFIX.'_payment_method');
         $payment_action = $order->get_meta('_'.PREFIX.'_payment_action');

         if( ! empty($payment_action) && $payment_method == $this->payment_method_type() ){

            $payment_action = json_encode($payment_action);

            ?>
            <div class="<?php echo PREFIX;?>-popup" style="display: none;">
               <div>
                  <div id="<?php echo PREFIX;?>-payment-action-data" class="<?php echo PREFIX;?>-component" data-payment_action='<?php echo esc_attr($payment_action);?>' data-order_id="<?php echo esc_attr($order_id);?>">
                     <div class="<?php echo PREFIX;?>-component__text"><?php _e('Processing...', 'convesiopay-woocommerce');?></div>
                  </div>
               </div>
            </div>
            <?php
         }

      }
   }



   /**
    * Get the payment action for order
    *
    * @return array|mixed|string
    */
   public function get_payment_action() {

      if(isset($_GET[PREFIX.'_payment_method'])){

         $order_id = get_query_var('order-pay', get_query_var('order-received', Util::array($_GET)->get('order_id')));
         $order    = wc_get_order($order_id);

         if( ! $order instanceof \WC_Order ){
            return [];
         }

         $payment_method = Util::array($_GET)->get(PREFIX.'_payment_method');
         $payment_action = $order->get_meta('_'.PREFIX.'_payment_action');

         if( ! empty($payment_action) && $payment_method == $this->payment_method_type() ){

            return $payment_action;

         }

      }

      return [];

   }



   /**
    * Get the shopper locale
    *
    * @return string
    */
   protected function get_locale() {
       return str_replace(['_informal', '_formal'], '', get_locale());
   }



   /**
    * Get the recurring stored payment method reference for the subscription renewal
    *
    * @return string
    */
   public function get_renewal_payment_reference($shopper_reference) {
      // Attempt to get the stored payment method for subscriptions payments made without an account, not logged in
      $response = Request::POST([
         'headers' => $this->api->headers(),
         'body'    => json_encode([
            'merchantAccount'  => $this->api->get_merchant(),
            'shopperReference' => $shopper_reference,
            'channel'          => 'Web',
         ])
      ])->send(Service_Util::get_payment_api_url('payment/v1/wc-plugin/payment-methods'));

      if ($response->status == 200 && !empty($response->body->storedPaymentMethods) && isset($response->body->storedPaymentMethods[0]->id)) {
         return $response->body->storedPaymentMethods[0]->id;
      }
      return '';
   }


}
