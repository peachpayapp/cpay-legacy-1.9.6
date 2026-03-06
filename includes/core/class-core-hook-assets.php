<?php
/**
 * Core Hook Assets
 *
 * @author ConvesioPay - based on the Woosa WC integration
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Core_Hook_Assets implements Interface_Hook_Assets{


   /**
    * Initiates.
    *
    * @return void
    */
   public static function init(){

      add_action('wp_enqueue_scripts', [__CLASS__ , 'public_assets'], 99);

      add_action('admin_enqueue_scripts', [__CLASS__ , 'admin_assets'], 99);

   }



   /**
    * Enqueues admin CSS/JS files.
    *
    * @return void
    */
   public static function public_assets(){

      if(is_checkout() || is_account_page()){

         $api = Service::checkout();
         $payment_methods = $api->list_payment_methods();
         $payment_methods['storedPaymentMethods'] = $api->get_ec_stored_cards();
         $store_card = Checkout::has_subscription() ? false : true;
         $store_card = is_user_logged_in() ? $store_card : false;

         Util::enqueue_scripts([
            apply_filters(PREFIX . '\core_hook_assets\public_assets', [
               'name' => 'util',
               'js' => [
                  'path' => untrailingslashit(plugin_dir_url(__FILE__)) . '/assets/js/',
                  'dependency' => ['jquery'],
                  'localize' => [
                     'translation' => [
                        'remove_card' => __('Are you sure you want to remove this card?', 'convesiopay-woocommerce'),
                        'remove_gdpr' => __('Are you sure you want to remove your personal information attached to this order payment?', 'convesiopay-woocommerce'),
                        'processing' => __('Processing...', 'convesiopay-woocommerce'),
                        'use_a_new_card' => __( 'Use a new card', 'convesiopay-woocommerce' ),
                        'google_error' => __( 'Sorry it looks like Google token is not generated, please refresh the page and click on GPay button!', 'convesiopay-woocommerce' ),
                     ],
                     'debug' => DEBUG,
                     'checkout_url' => wc_get_checkout_url(),
                     'locale' => get_user_locale(),
                     'api' => [
                        'origin_key' => Service_Util::get_origin_key(),
                        'environment' => $api->get_env(),
                        'adyen_merchant' => $api->get_merchant(),
                        'card_types' => $api->get_card_types(),
                        'response_payment_methods' => $payment_methods,
                        'store_card' => $store_card,
                        'has_holder_name' => true,
                        'holder_name_required' => apply_filters(PREFIX . '\validate_fields\require_cardholder_name', true),
                     ],
                     'site_name' => get_bloginfo('name'),
                     'currency' => get_woocommerce_currency(),
                     'cart' => [
                        'country' => is_checkout() ? WC()->customer->get_shipping_country() : '',
                        'total' => is_checkout() ? WC()->cart->total : '',
                     ],
                  ]
               ],
            ]),
         ]);
      }

   }



   /**
    * Enqueues public CSS/JS files.
    *
    * @return void
    */
   public static function admin_assets(){

      Util::enqueue_scripts([
         [
            'name' => 'util',
            'js' => [
               'path' => untrailingslashit(plugin_dir_url(__FILE__)) . '/assets/js/',
               'dependency' => ['jquery'],
               'localize' => [
                  'translation' => [
                     'perform_action' => __('Are you sure you want to perform this action?', 'convesiopay-woocommerce'),
                  ]
               ]
            ]
         ],
         [
            'name' => 'core',
            'css' => [
               'path' => untrailingslashit(plugin_dir_url(__FILE__)) . '/assets/css/',
            ],
            'js' => [
               'path' => untrailingslashit(plugin_dir_url(__FILE__)) . '/assets/js/',
               'dependency' => [PREFIX . '-util'],
            ],
         ],
      ]);
   }

}