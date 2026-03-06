<?php
/**
 * Credit Card
 *
 * @author ConvesioPay - based on the Woosa WC integration
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
use Automattic\WooCommerce\StoreApi\Utilities\NoticeHandler;

defined( 'ABSPATH' ) || exit;


class Credit_Card extends Abstract_Gateway{


   /**
    * Constructor of this class.
    *
    * @param bool $init_hooks
    * @since 1.0.0
    */
   public function __construct($init_hooks = true){

      parent::__construct($init_hooks);

      $this->has_fields = true;

      $this->supports = array_merge($this->supports, [
         'subscriptions',
         'subscription_cancellation',
         'subscription_suspension',
         'subscription_reactivation',
         'subscription_amount_changes',
         'subscription_date_changes',
         // 'subscription_payment_method_change',
         // 'subscription_payment_method_change_customer',
         // 'subscription_payment_method_change_admin',
         'multiple_subscriptions'
      ]);

      add_filter(PREFIX . '\validate_fields\require_cardholder_name', [$this, 'require_cardholder_name']);

   }



   /**
    * Gets default payment method title.
    *
    * @since 1.0.0
    * @return string
    */
   public function get_default_title(){
      return __('ConvesioPay - Credit Card', 'convesiopay-woocommerce');
   }



   /**
    * Gets default payment method description.
    *
    * @since 1.1.0 - display supported countries
    * @since 1.0.0
    * @return string
    */
   public function get_default_description(){
      return $this->show_supported_country();
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
      return 'scheme';
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
    * Adds extra fields.
    *
    * @since 1.0.0
    * @return void
    */
    public function payment_fields() {

      parent::payment_fields();

      echo $this->generate_extra_fields_html();

   }



   /**
    * Generates extra fields HTML.
    *
    * @since 1.1.1 - add the method type in field names
    * @since 1.0.3 - added installments field
    * @since 1.0.0
    * @return string
    */
   public function generate_extra_fields_html(){

      $type = $this->payment_method_type();
      $installments = ( $this->get_option('allow_installments') === 'yes' ) ? Service_Util::get_installments_by_country( WC()->customer->get_shipping_country(), $this->get_option('installments') ) : '';

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
         <input type="hidden" id="<?php echo esc_attr(PREFIX . "-{$type}-card-installments");?>" name="<?php echo esc_attr(PREFIX . "-{$type}-card-installments");?>" />

         <input type="hidden" data-<?php echo PREFIX?>-card-installments value='<?php echo esc_attr(json_encode($installments));?>' />

      </div>
      <?php
   }



   /**
    * Get the card installments
    *
    * @return string|null
    */
   public function get_installments() {
      $installments = [];

      if (is_checkout() && !empty(WC()->customer)) {

         $installments = ( $this->get_option('allow_installments') === 'yes' ) ? Service_Util::get_installments_by_country( WC()->customer->get_shipping_country(), $this->get_option('installments') ) : '';

      }

      return json_encode($installments);
   }



   /**
    * Renders the form for card fields.
    *
    * @since 1.1.1 - improve custom attributes and made the logic more clean
    * @since 1.0.6 - remove duplicated card-form element
    * @since 1.0.3
    * @return string
    */
   public function render_card_form(){

      $type = $this->payment_method_type();
      $cards = $this->api->checkout()->get_ec_stored_cards();

      ?>

      <div class="<?php echo PREFIX;?>-stored-cards">

         <?php foreach($cards as $index => $item):

            //exclude BCMC from Credit Cards and Credit Cards from BCMC
            if( ('bcmc' === $this->payment_method_type() && 'bcmc' !== $item['brand']) ||
            ('scheme' === $this->payment_method_type() && 'bcmc' === $item['brand']) ){
               continue;
            }
            ?>

            <div class="<?php echo PREFIX;?>-stored-card is-stored-card">
               <div class="<?php echo PREFIX;?>-stored-card__details" data-<?php echo PREFIX;?>-stored-card="<?php echo esc_attr(PREFIX . "-{$type}-card-{$index}");?>" data-<?php echo PREFIX;?>-stored-card-type="<?php echo esc_attr($type);?>">
                  <img src="<?php echo esc_attr('https://checkoutshopper-test.adyen.com/checkoutshopper/images/logos/' . $item['brand']);?>.svg" alt="">
                  <div>******<?php echo esc_html($item['lastFour']);?></div>
               </div>
               <div class="<?php echo PREFIX;?>-stored-card__fields" style="display: none;" id="<?php echo esc_attr(PREFIX . "-{$type}-card-{$index}");?>"></div>
            </div>
         <?php endforeach; ?>

         <div class="<?php echo PREFIX;?>-stored-card">
            <div class="<?php echo PREFIX;?>-stored-card__details" data-<?php echo PREFIX;?>-stored-card="<?php echo esc_attr(PREFIX . "-{$type}-card-new");?>" data-<?php echo PREFIX;?>-stored-card-type="<?php echo esc_attr($type);?>">
               <span class="dashicons dashicons-plus"></span>
               <div><?php _e('Use a new card', 'convesiopay-woocommerce');?></div>
            </div>
            <div class="<?php echo PREFIX;?>-stored-card__fields" id="<?php echo esc_attr(PREFIX . "-{$type}-card-new");?>" style="display: none;">
               <div id="<?php echo PREFIX;?>-card-form"></div>
            </div>
         </div>

      </div>

      <?php

   }



   /**
    * Get the stored cards
    *
    * @return array
    */
   public function get_stored_cards() {

      $cards = $this->api->checkout()->get_ec_stored_cards();

      $stored_cards = [];

      foreach ($cards as $index => $item){

         //exclude BCMC from Credit Cards and Credit Cards from BCMC
         if (('bcmc' === $this->payment_method_type() && 'bcmc' !== $item['brand']) ||
            ('scheme' === $this->payment_method_type() && 'bcmc' === $item['brand'])) {
            continue;
         }

         $stored_cards[] = $item;
      }

      return $stored_cards;


   }



   /**
    * Validates extra fields.
    *
    * @since 1.1.1 - use always the method type in field names
    * @since 1.0.3 - add support for installments field
    * @since 1.0.0
    * @return bool
    */
   public function validate_fields() {

      $is_valid = parent::validate_fields();
      $type = $this->payment_method_type();

      $card_number    = Util::array($_POST)->get(PREFIX."-{$type}-card-number");
      $card_brand     = Util::array($_POST)->get(PREFIX."-{$type}-card-brand");
      $card_exp_month = Util::array($_POST)->get(PREFIX."-{$type}-card-exp-month");
      $card_exp_year  = Util::array($_POST)->get(PREFIX."-{$type}-card-exp-year");
      $card_cvc       = Util::array($_POST)->get(PREFIX."-{$type}-card-cvc");
      $card_holder    = Util::array($_POST)->get(PREFIX."-{$type}-card-holder");

      $is_stored_card = Util::array($_POST)->get(PREFIX."-{$type}-is-stored-card", 'no');
      $stored_card_id = Util::array($_POST)->get(PREFIX."-{$type}-sci");

      $installments = (int) Util::array($_POST)->get(PREFIX."-{$type}-card-installments");
      $country      = Util::array($_POST)->get('billing_country');


      if( $installments > 0 && ! Service_Util::is_valid_installment($installments, $country, $this->get_option('installments'), $this->get_option('allow_installments')) ){
         wc_add_notice(__('Sorry, the number of installments seems invalid, please try again', 'convesiopay-woocommerce'), 'error');
         $is_valid = false;
      }

      if( 'yes' === $is_stored_card ){

         if(empty($stored_card_id)){
            $is_valid = false;
            wc_add_notice(__('Please provide the CVC/CVV of the card.', 'convesiopay-woocommerce'), 'error');
         }

      }else{

         if(empty($card_number)){
            $is_valid = false;
            wc_add_notice(__('Please provide the card number.', 'convesiopay-woocommerce'), 'error');
         }

         if(empty($card_exp_month)){
            $is_valid = false;
            wc_add_notice(__('Please provide the card expiration month.', 'convesiopay-woocommerce'), 'error');
         }

         if(empty($card_exp_year)){
            $is_valid = false;
            wc_add_notice(__('Please provide the card expiration year.', 'convesiopay-woocommerce'), 'error');
         }

         if(empty($card_holder) && apply_filters(PREFIX . '\validate_fields\require_cardholder_name', true)){
            $is_valid = false;
            wc_add_notice(__('Please provide the card holder name.', 'convesiopay-woocommerce'), 'error');
         }

         if(empty($card_cvc) && apply_filters(PREFIX . '\validate_fields\require_cvc', false)){
            $is_valid = false;
            wc_add_notice(__('Please provide your card security number (CVC).', 'convesiopay-woocommerce'), 'error');
         }
      }

      return $is_valid;
   }



   /**
    * Builds the required payment payload
    *
    * @since 1.1.1 - use always the method type in field names
    * @since 1.1.0 - use parent function to get common data
    * @since 1.0.9 - add fallback for splitting address
    * @since 1.0.7 - use the shopper reference from the metadata
    * @since 1.0.6 - do not allow storing cards for guest users
    *              - add billing address
    * @since 1.0.4 - save stored card id as `recurringDetailReference`
    * @since 1.0.3 - add support for installments field
    * @since 1.0.0
    * @param \WC_Order $order
    * @param string $reference
    * @return array
    */
   protected function build_payment_payload(\WC_Order $order, $reference){

      $type = $this->payment_method_type();

      $card_number    = Util::array($_POST)->get(PREFIX."-{$type}-card-number");
      $card_brand     = Util::array($_POST)->get(PREFIX."-{$type}-card-brand");
      $card_exp_month = Util::array($_POST)->get(PREFIX."-{$type}-card-exp-month");
      $card_exp_year  = Util::array($_POST)->get(PREFIX."-{$type}-card-exp-year");
      $card_cvc       = Util::array($_POST)->get(PREFIX."-{$type}-card-cvc");
      $card_holder    = Util::array($_POST)->get(PREFIX."-{$type}-card-holder");

      $is_stored_card = Util::array($_POST)->get(PREFIX."-{$type}-is-stored-card", 'no');
      $stored_card_id = Util::array($_POST)->get(PREFIX."-{$type}-sci");

      $installments = (int) Util::array($_POST)->get(PREFIX."-{$type}-card-installments");
      $store_card   = (bool) Util::array($_POST)->get(PREFIX."-{$type}-store-card");
      $store_card   = is_user_logged_in() ? $store_card : false;

      $payload = array_merge( parent::build_payment_payload($order, $reference), [
         'additionalData' => [
            'allow3DS2' => true,
         ],
         'storePaymentMethod' => $store_card
      ]);


      if( 'yes' === $is_stored_card ){
         $payload['paymentMethod']['storedPaymentMethodId'] = $stored_card_id;
         $payload['paymentMethod']['brand'] = $card_brand;
      }else{
         $payload['paymentMethod']['encryptedCardNumber'] = $card_number;
         $payload['paymentMethod']['brand'] = $card_brand;
         $payload['paymentMethod']['encryptedExpiryMonth'] = $card_exp_month;
         $payload['paymentMethod']['encryptedExpiryYear'] = $card_exp_year;
         $payload['paymentMethod']['holderName'] = $card_holder;
      }


      if( ! empty($card_cvc) ){
         $payload['paymentMethod']['encryptedSecurityCode'] = $card_cvc;
      }

      if( $installments > 0 ){
         $payload['installments']['value'] = $installments;
      }


      return $payload;
   }



   /**
    * Processes the payment.
    *
    * @since 1.1.0 - replace `_#subscription#_` with `-S`
    * @since 1.0.0
    * @param int $order_id
    * @return array
    */
   public function process_payment($order_id) {

      parent::process_payment($order_id);

      $order          = wc_get_order($order_id);
      $reference      = $order_id;
      $payload        = $this->build_payment_payload( $order, $reference );
      $stored_card_id = Util::array($_POST)->get(PREFIX . '-' . $this->payment_method_type() . '-sci');

      $recurr_reference = [];
      $subscriptions    = $this->get_subscriptions_for_order( $order_id );
      $subscription_ids = [];

      //recurring payments
      if(count($subscriptions) > 0){

         foreach($subscriptions as $sub_id => $item){
            $subscription_ids[$sub_id] = $sub_id;
            $recurr_reference[] = $sub_id;

            if (!($item instanceof \WC_Order)) {
               $item = wc_get_order($sub_id);
            }

            // Add the card ID and shopper reference
            $item->update_meta_data('_' . PREFIX . '_recurringDetailReference', $stored_card_id);
            $item->update_meta_data('_' . PREFIX . '_shopper_reference', Service_Util::get_shopper_reference() );
            // Ensure it is saved
            $item->save();
         }

         $reference = \implode('-S', $recurr_reference);
         $reference = $order_id.'-S'.$reference;
         $payload = $this->build_payment_payload( $order, $reference );

         //for tokenizing must be `true`
         $payload['storePaymentMethod'] = true;

         //create a list with unpaid subscriptions
         $order->update_meta_data('_'.PREFIX.'_unpaid_subscriptions', $subscription_ids);

      }

      $response = $this->api->checkout()->send_payment($payload);

      if($response->status == 200){

         return $this->process_payment_result( $response, $order );

      }else{

         wc_add_notice($response->body->message, 'error');

         NoticeHandler::convert_notices_to_exceptions( 'woocommerce_rest_payment_error' );

      }


      return array('result' => 'failure');

   }



   /**
    * Processes the payment result.
    *
    * @since 1.0.6 - use default checkout url if there are subscriptions
    * @since 1.0.4 - combine all actions in one popup
    * @since 1.0.0
    * @param object $response
    * @param \WC_Order $order
    * @return array
    */
   protected function process_payment_result( $response, $order ){

      $body        = Util::obj_to_arr($response->body);
      $result_code = Util::array($body)->get('resultCode');
      $reference   = Util::array($body)->get('pspReference');
      $action      = Util::array($body)->get('action');

      $extra_action_codes = ['RedirectShopper', 'ChallengeShopper', 'IdentifyShopper',];
      $error_codes = ['Refused', 'Error', 'Cancelled'];

      $result = [
         'result'   => 'success',
         'redirect' => Service_Util::get_return_page_url($order, $result_code)
      ];

      $order->read_meta_data();
      $order_psp_reference = $order->get_meta('_'.PREFIX.'_payment_pspReference');

      if ($order_psp_reference !== $reference) {

         $order->update_meta_data('_'.PREFIX.'_payment_pspReference', $reference);

      }

      $order->update_meta_data('_' . PREFIX . '_payment_resultCode', $result_code);
      $order->update_meta_data('_' . PREFIX . '_payment_action', $action);
      $order->save();

      if(in_array($result_code, $extra_action_codes)){

         $checkout_url = Checkout::has_subscription() ? wc_get_checkout_url() : $order->get_checkout_payment_url();

         //redirect to process payment action via Web Component
         $result = [
            'result'   => 'success',
            'redirect' => add_query_arg(
               [
                  PREFIX . '_payment_method' => $this->payment_method_type(),
                  PREFIX . '_order_id'       => $order->get_id(),
               ],
               $checkout_url
            )
         ];

      }elseif(in_array($result_code, $error_codes)){

         wc_add_notice(__('The transaction could not be processed! Either it was refused or an error has occurred! Please make sure you provide the valid card details or try with a different card.', 'convesiopay-woocommerce'), 'error');

         NoticeHandler::convert_notices_to_exceptions( 'woocommerce_rest_payment_error' );

         $result = [
            'result' => 'failure'
         ];

      }

      return $result;
   }



   /**
    * Adds an array of fields to be displayed on the gateway's settings screen.
    *
    * @since 1.0.3
    * @return void
    */
   public function init_form_fields() {

      parent::init_form_fields();

      //only for world wide cards
      if('scheme' === $this->payment_method_type()){
         $this->form_fields = array_merge($this->form_fields, array(
            'allow_installments'    => array(
               'title'       => __('Allow Installments', 'convesiopay-woocommerce'),
               'label'       => __('Yes', 'convesiopay-woocommerce'),
               'type'        => 'checkbox',
               'default'     => 'no',
               'desc_tip'    => __('Whether or not to allow installments. This is only for Brazil, Mexico and Turkey', 'convesiopay-woocommerce'),
            ),
            'installments'    => array(
               'title'       => __('Installments number', 'convesiopay-woocommerce'),
               'type'        => 'number',
               'default'     => '20',
               'desc_tip'    => __('The maximum number for installments (default: 20).', 'convesiopay-woocommerce'),
            ),
            'require_card_holder_name' => array(
               'title'       => __('Require card holder name', 'convesiopay-woocommerce'),
               'label'       => __('Yes', 'convesiopay-woocommerce'),
               'type'        => 'checkbox',
               'default'     => 'yes',
               'desc_tip'    => __('When checked the card holder field is not required.', 'convesiopay-woocommerce'),
            ),
         ));
      }
   }



   /**
    * Check if the cardholder name is required
    *
    * @param $is_required
    * @return bool
    */
   public function require_cardholder_name($is_required) {

      if ( $this->get_option('require_card_holder_name', 'yes') !== 'yes' ) {
         return false;
      }

      return $is_required;

   }


}
