<?php
/**
 * Ideal
 *
 * Payment type     : Online banking
 * Payment flow     : Redirect
 * Countries        : NL
 * Currencies       : EUR
 * Recurring        : Yes
 * Refunds          : Yes
 * Partial refunds  : Yes
 * Separate captures: No
 * Chargebacks      : No
 *
 * @author ConvesioPay - based on the Woosa WC integration
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
use Automattic\WooCommerce\StoreApi\Utilities\NoticeHandler;

defined( 'ABSPATH' ) || exit;


class Ideal extends Abstract_Gateway{


   /**
    * Constructor of this class.
    *
    * @param bool $init_hooks
    * @since 1.0.0
    */
    public function __construct($init_hooks = true){

      parent::__construct($init_hooks);

      $this->has_fields = true;

      if( $this->support_recurring() ){
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
      }
   }



   /**
    * List of countries where is available.
    *
    * @since 1.1.0
    * @return array
    */
   public function available_countries(){

      return [
         'NL' => [
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
      return __('ConvesioPay - iDEAL', 'convesiopay-woocommerce');
   }



   /**
    * Gets default payment method description.
    *
    * @since 1.1.0 - display supported countries
    * @since 1.0.0
    * @return string
    */
   public function get_default_description(){
      return sprintf(__('In order to support recurring payments with iDeal you have to enable SEPA Direct Debit first. %s', 'convesiopay-woocommerce'), '<br/>'.$this->show_supported_country());
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
      return 'ideal';
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

      parent::payment_fields();

      echo $this->generate_extra_fields_html();

   }



   /**
    * Generates extra fields HTML.
    *
    * @since 1.1.0 - added CSS class
    * @since 1.0.0
    * @return string
    */
   public function generate_extra_fields_html(){

      ?>
      <div id="<?php echo esc_attr(PREFIX . '-' . $this->payment_method_type() . '-container');?>"></div>
      <input type="hidden" id="<?php echo esc_attr(Util::prefix($this->payment_method_type() . '_issuer'));?>" name="<?php echo esc_attr(Util::prefix($this->payment_method_type() . '_issuer'));?>">
      <?php
   }



   /**
    * Validates extra added fields.
    *
    * @since 1.0.0
    * @return bool
    */
   public function validate_fields() {

      $is_valid = parent::validate_fields();
      $issuer = Util::array($_POST)->get( Util::prefix($this->payment_method_type() . '_issuer') );

      if(empty($issuer)){
         $is_valid = false;
         wc_add_notice(__('Please select your bank account.', 'convesiopay-woocommerce'), 'error');
      }

      return $is_valid;
   }



   /**
    * Builds the required payment payload
    *
    * @since 1.1.0 - use parent function to get common data
    * @since 1.0.0
    * @param \WC_Order $order
    * @param string $reference
    * @return array
    */
   protected function build_payment_payload(\WC_Order $order, $reference){

      $issuer = Util::array($_POST)->get( Util::prefix($this->payment_method_type() . '_issuer') );

      $payload = parent::build_payment_payload($order, $reference);

      $payload['paymentMethod']['issuer'] = $issuer;

      return $payload;
   }



   /**
    * Processes the payment.
    *
    * @since 1.1.0 - replace `_#subscription#_` with `-S`
    * @since 1.0.7 - use \WC_Order instance to manipulate metadata
    * @since 1.0.0
    * @param int $order_id
    * @return array
    */
   public function process_payment($order_id) {

      parent::process_payment($order_id);

      $order     = wc_get_order($order_id);
      $reference = $order_id;
      $payload   = $this->build_payment_payload( $order, $reference );

      $recurr_reference = [];
      $subscriptions    = $this->get_subscriptions_for_order( $order_id );
      $subscription_ids = [];

      //recurring payments
      if(count($subscriptions) > 0){

         foreach($subscriptions as $sub_id => $item){
            $subscription_ids[$sub_id] = $sub_id;
            $recurr_reference[] = $sub_id;
         }

         $reference = \implode('-S', $recurr_reference);
         $reference = $order_id.'-S'.$reference;
         $payload = $this->build_payment_payload( $order, $reference );

         //for tokenizing
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

      $extra_action_codes = ['RedirectShopper',];
      $error_codes = ['Refused', 'Error', 'Cancelled'];

      $result = [
         'result'   => 'success',
         'redirect' => Service_Util::get_return_page_url($order, $result_code)
      ];

      $order->update_meta_data('_' . PREFIX . '_payment_resultCode', $result_code);
      $order->update_meta_data('_' . PREFIX . '_payment_action', $action);
      $order->save();

      if(in_array($result_code, $extra_action_codes)){

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

      }elseif(in_array($result_code, $error_codes)){

         wc_add_notice(__('The transaction could not be processed! Either it has been refused or an error has occurred! Please make sure you provide a valid bank account.', 'convesiopay-woocommerce'), 'error');

         NoticeHandler::convert_notices_to_exceptions( 'woocommerce_rest_payment_error' );

         $result = [
            'result' => 'failure'
         ];

      }

      return $result;

   }


}