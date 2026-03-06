<?php
/**
 * MOLPay online banking
 *
 * Payment type     : Online banking
 * Payment flow     : Redirect
 * Countries        : -
 * Currencies       : -
 * Recurring        : No
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


class MOLPay extends Abstract_Gateway{


   /**
    * Constructor of this class.
    *
    * @param bool $init_hooks
    * @since 1.5.0
    */
    public function __construct($init_hooks = true){

      parent::__construct($init_hooks);

      $this->has_fields = true;

   }



   /**
    * Gets default payment method title.
    *
    * @since 1.5.0
    * @return string
    */
   public function get_default_title(){
      return __('ConvesioPay - MOLPay', 'convesiopay-woocommerce');
   }



   /**
    * Gets default payment method description.
    *
    * @since 1.5.0
    * @return string
    */
   public function get_default_description(){
      return sprintf(__('When making a MOLPay online banking payment, the shopper is redirected to their bank\'s website or app to complete the payment.  %s', 'convesiopay-woocommerce'), '<br/>'.$this->show_supported_country());
   }



   /**
    * Gets default description set in settings.
    *
    * @since 1.5.0
    * @return void
    */
   public function get_settings_description(){}



   /**
    * Type of the payment method (e.g ideal, scheme. bcmc).
    *
    * @since 1.5.0
    * @return string
    */
   public function payment_method_type(){
      return 'molpay';
   }



   /**
    * Returns the payment method to be used for recurring payments
    *
    * @since 1.5.0
    * @return void
    */
   public function recurring_payment_method(){}



   /**
    * Adds extra fields.
    *
    * @since 1.5.0
    * @return void
    */
   public function payment_fields() {

      parent::payment_fields();

      echo $this->generate_extra_fields_html();

   }



   /**
    * Generates extra fields HTML.
    *
    * @since 1.5.0
    * @return string
    */
   public function generate_extra_fields_html(){

      ?>
      <div id="<?php echo PREFIX;?>-<?php echo $this->payment_method_type();?>-container"></div>
      <input type="hidden" id="<?php echo Util::prefix($this->payment_method_type() . '_issuer');?>" name="<?php echo Util::prefix($this->payment_method_type() . '_issuer');?>">
      <?php
   }



   /**
    * Validates extra added fields.
    *
    * @since 1.5.0
    * @return bool
    */
   public function validate_fields() {

      $is_valid = parent::validate_fields();
      $issuer = $this->get_issuer();

      if(empty($issuer)){
         $is_valid = false;
         wc_add_notice(__('Please select your bank account.', 'convesiopay-woocommerce'), 'error');
      }

      return $is_valid;
   }



   /**
    * Builds the required payment payload
    *
    * @since 1.5.0
    * @param \WC_Order $order
    * @param string $reference
    * @return array
    */
   protected function build_payment_payload(\WC_Order $order, $reference){

      $issuer = $this->get_issuer();

      $payload = parent::build_payment_payload($order, $reference);

      $payload['paymentMethod']['issuer'] = $issuer;

      return $payload;
   }



   /**
    * Get the issuer from POST using payment method key or lower key method key
    *
    * @return string|null
    */
   protected function get_issuer() {

      return Util::array($_POST)->get(
         Util::prefix($this->payment_method_type() . '_issuer'),
         Util::array($_POST)->get(strtolower(Util::prefix($this->payment_method_type() . '_issuer')))
      );

   }



   /**
    * Processes the payment.
    *
    * @since 1.5.0
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
    * @since 1.5.0
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

      $order->update_meta_data('_' . PREFIX . '_payment_resultCode', $result_code);
      $order->update_meta_data('_' . PREFIX . '_payment_action', $action);
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