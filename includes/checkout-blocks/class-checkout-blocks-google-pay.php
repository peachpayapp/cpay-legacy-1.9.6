<?php
/**
 * Checkout blocks Google Pay
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;

class Checkout_Blocks_Google_Pay extends AbstractPaymentMethodType {

   /**
    * @var Abstract_Gateway
    */
   private $gateway;

   protected $name = 'woosa_adyen_googlepay';


   /**
    * Initialize the payment block
    *
    * @return void
    */
   public function initialize() {

      $this->gateway = new Googlepay(false);
      $this->settings = $this->gateway->settings;

   }


   /**
    * Is active
    *
    * @return bool
    */
   public function is_active() {
      return $this->gateway->is_available();
   }



   /**
    * Register the payment block script
    *
    * @return string[]
    */
   public function get_payment_method_script_handles() {

      $script_asset_path = dirname( FILE_NAME ) . '/build/checkout-blocks-googlepay.asset.php';

      $script_asset      = file_exists( $script_asset_path )
         ? require $script_asset_path
         : [
            'dependencies' => [],
            'version'      => VERSION,
         ];

      Util::enqueue_scripts([
         [
            'name' => 'checkout-blocks-googlepay',
            'js' => [
               'path' => untrailingslashit(plugin_dir_url(FILE_NAME)) . '/build/',
               'dependency' => array_merge($script_asset['dependencies'], [
                  'wc-blocks-registry',
                  'wc-settings',
                  'wp-element',
                  'wp-html-entities',
                  'wp-i18n',
               ]),
               'version' => $script_asset['version'],
            ]
         ],
      ]);

      return [ PREFIX . '-checkout-blocks-googlepay' ];

   }



   /**
    * Get payment method data
    *
    * @return array
    */
   public function get_payment_method_data() {

      return [
         'title' => $this->gateway->title,
         'description' => $this->gateway->description,
         'merchant_identifier' => $this->gateway->get_option('merchant_identifier'),
         'testmode' => $this->gateway->get_option('testmode', 'yes'),
         'google_description' => WC()->session ? WC()->session->get( 'woosa_adyen_googlepay_description' ) : '',
         'token' => WC()->session ? WC()->session->get( 'woosa_adyen_googlepay_token' ) : '',
         'icon' => $this->gateway->get_icon_url(),
         'supports' => $this->gateway->supports,
         'payment_action' => $this->gateway->get_payment_action(),
      ];

   }

}
