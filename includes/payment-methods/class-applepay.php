<?php
/**
 * Apple Pay
 *
 * @author ConvesioPay - based on the Woosa WC integration
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
use Automattic\WooCommerce\StoreApi\Utilities\NoticeHandler;

defined( 'ABSPATH' ) || exit;


class Applepay extends Abstract_Gateway{


   /**
    * Constructor of this class.
    * @param bool $init_hooks
    */
    public function __construct($init_hooks = true){

      parent::__construct($init_hooks);

      $this->has_fields = true;
   }



   /**
    * Gets default payment method title.
    *
    * @return string
    */
   public function get_default_title(){
      return __('ConvesioPay - Apple Pay', 'convesiopay-woocommerce');
   }



   /**
    * Gets default payment method description.
    *
    * @since 1.1.0 - display supported countries
    * @since 1.0.4
    * @return string
    */
   public function get_default_description(){
      return $this->show_supported_country();
   }



   /**
    * Gets default description set in settings.
    *
    * @return string
    */
   public function get_settings_description(){}



   /**
    * Type of the payment method (e.g ideal, scheme. bcmc).
    *
    * @return string
    */
   public function payment_method_type(){
      return 'applepay';
   }


   /**
    * Returns the payment method to be used for recurring payments
    *
    * @return string
    */
   public function recurring_payment_method(){}



   /**
    * Adds an array of fields to be displayed on the gateway's settings screen.
    *
    * @since 1.1.0
    * @return void
    */
   public function init_form_fields() {

      $is_enabled = parent::init_form_fields();

      if( $is_enabled === false ) return;

      if('applepay' === $this->payment_method_type()){
         $this->form_fields = array_merge($this->form_fields, array(
            'config_desc' => [
               'title' => '',
               'label' => '',
               'type'  => 'config_desc',
            ],
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
            <h3><?php _e('Configuration', 'convesiopay-woocommerce');?></h3>
            <ol>
               <li>
                  <p><?php printf(__('Download and unzip the %sConvesioPay\'s Apple Pay certificate%s', 'convesiopay-woocommerce'), '<a href="https://convesiopay.com/assets/apple-developer-merchantid-domain-association-2024.zip" target="_blank">', '</a>');?></p>
               </li>
               <li>
                  <p><?php printf(__('Host the certificate file with the name %sapple-developer-merchantid-domain-association%s on each domain you want to use, including subdomains, under the following path: %s', 'convesiopay-woocommerce'), '<b>', '</b>', '<code>/.well-known/apple-developer-merchantid-domain-association</code>');?></p>
                  <p><?php _e('The file must:', 'convesiopay-woocommerce');?></p>
                  <ul style="list-style: disc;padding-left: 20px;">
                     <li><?php _e('Be externally accessible.', 'convesiopay-woocommerce');?></li>
                     <li><?php _e('Not be password protected.', 'convesiopay-woocommerce');?></li>
                     <li><?php _e('The file cannot be behind a proxy or redirect.', 'convesiopay-woocommerce');?></li>
                  </ul>
                  <p><?php printf(__('See an example of a %sworking certificate file.%s', 'convesiopay-woocommerce'), '<a href="https://eu.adyen.link/.well-known/apple-developer-merchantid-domain-association" target="_blank">', '</a>');?></p>
               </li>
               <li>
                  <p><?php printf(__('%sAdd ApplePay domains in the ConvesioPay Dashboard WooCommerce Integration Settings%s. You must register all top-level domains and subdomains you want to take ApplePay payments for with this extension.', 'convesiopay-woocommerce'), '<a href="https://convesiopay.com/integrations/woo-commerce" target="_blank">', '</a>');?></p>
               </li>
               <li>
                 <p>After you add ApplePay domains, you will need to clear the plugin cache on the <a href="<?php echo SETTINGS_URL . '&tab=tools'; ?>">Tools page</a>.</p>
               </li>
              <br>
               <p style="font-style: italic;">If you need assistance setting this up, please reach out to <a href="mailto:pay@convesio.com">ConvesioPay Support</a>.</p>
            </ol>
         </td>
      </tr>
      <?php
      return ob_get_clean();
   }



   /**
    * Adds extra fields.
    *
    * @return void
    */
    public function payment_fields() {

      parent::payment_fields();

      echo $this->generate_extra_fields_html();

   }



   /**
    * Generates extra fields HTML.
    *
    * @return string
    */
   public function generate_extra_fields_html(){

      ?>
      <div id="applepay-container"></div>
      <input type="hidden" id="<?php echo esc_attr($this->id . '_token');?>" name="<?php echo esc_attr($this->id . '_token');?>">
      <?php
   }


   /**
    * Validates extra added fields.
    *
    * @return bool
    */
   public function validate_fields() {

      $is_valid = parent::validate_fields();
      $token = Util::array($_POST)->get($this->id.'_token');

      if(empty($token)){
         wc_add_notice(__('Sorry it looks like Apple Pay token is not generated, please refresh the page and try again!', 'convesiopay-woocommerce'), 'error');
         $is_valid = false;
      }

      return $is_valid;
   }



   /**
    * Builds the required payment payload
    *
    * @param \WC_Order $order
    * @param string $reference
    * @return array
    */
   protected function build_payment_payload(\WC_Order $order, $reference){

      $token = stripslashes(Util::array($_POST)->get($this->id.'_token'));

      $payload = array_merge( parent::build_payment_payload($order, $reference), [
         'paymentMethod' => [
            'type' => $this->payment_method_type(),
            'applepay.token' => $token
         ]
      ]);


      return $payload;
   }



   /**
    * Processes the payment.
    *
    * @param int $order_id
    * @return array
    */
   public function process_payment($order_id) {

      parent::process_payment($order_id);

      $order    = wc_get_order($order_id);
      $payload  = $this->build_payment_payload($order, $order_id);

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
    * @since 1.3.2
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

      $result_code = Util::array($body)->get('resultCode');
      $reference   = Util::array($body)->get('pspReference');
      $action      = Util::array($body)->get('action');

      $order->read_meta_data();
      $order_psp_reference =  $order->get_meta('_'.PREFIX.'_payment_pspReference');

      if ($order_psp_reference !== $reference) {

         $order->update_meta_data('_'.PREFIX.'_refund_pspReference', $reference);
      }

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