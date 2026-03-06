<?php
/**
 * Checkout blocks Bancontact
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;

class Checkout_Blocks_Bancontact extends AbstractPaymentMethodType {

   /**
    * @var Abstract_Gateway
    */
   private $gateway;

   protected $name = 'woosa_adyen_bancontact';


   /**
    * Initialize the payment block
    *
    * @return void
    */
   public function initialize() {

      $this->gateway = new Bancontact(false);
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

      $script_asset_path = dirname( FILE_NAME ) . '/build/checkout-blocks-bancontact.asset.php';

      $script_asset      = file_exists( $script_asset_path )
         ? require $script_asset_path
         : [
            'dependencies' => [],
            'version'      => VERSION,
         ];

      Util::enqueue_scripts([
         [
            'name' => 'checkout-blocks-bancontact',
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

      return [ PREFIX . '-checkout-blocks-bancontact' ];

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
         'icon' => $this->gateway->get_icon_url(),
         'get_installments' => $this->gateway->get_installments(),
         'stored_cards' => $this->gateway->get_stored_cards(),
         'supports' => $this->gateway->supports,
         'payment_action' => $this->gateway->get_payment_action(),
      ];

   }

}
