<?php
/**
 * Authorization Hook
 *
 * @author ConvesioPay - based on the Woosa WC integration
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Authorization_Hook implements Interface_Hook{


   /**
    * Initiates the hooks.
    *
    * @return void
    */
   public static function init(){

      add_action(PREFIX . '\authorization\output_section\fields', [__CLASS__, 'add_section_fields']);

      add_filter(PREFIX . '\authorization\connect', [__CLASS__, 'connect_env'], 90);

   }



   /**
    * Displays section fields.
    *
    * @param Module_Authorizaton $authorization
    * @return string
    */
   public static function add_section_fields($authorization){

      $show_origin_keys = '';
      $origin_keys = get_option( PREFIX . '_origin_keys', [] );

      if(empty($origin_keys)){
         $show_origin_keys = '<span style="color: #a30000;">'.__( 'No origin key found, please make sure you provided all the information below and hit the "Save Changes" button!', 'convesiopay-woocommerce' ).'</span>';
      }else{

         foreach($origin_keys as $org_domain => $org_key){
            $show_origin_keys .= '<code>'. $org_domain . '</code> - <code style="word-break: break-all;">'.$org_key.'</code>';
         }
      }

      echo Util::get_template('section-fields.php', [
         'authorization' => $authorization,
         'show_origin_keys' => $show_origin_keys,
      ], dirname(__FILE__), '/templates');
   }



   /**
    * Grant the access to the service.
    *
    * @param array $output
    * @return array
    */
   public static function connect_env($output){

      $api = Service::checkout();

      $response = Request::POST([
         'headers' => $api->headers(),
         'body'    => json_encode([
            'merchantAccount'  => $api->get_merchant(),
            'shopperReference' => Service_Util::get_shopper_reference(),
            'channel'          => 'Web',
         ])
      ])->send(Service_Util::get_payment_api_url('payment/v1/wc-plugin/payment-methods'));

      if($response->status == 200){

         Service_Util::generate_origin_keys(false);

      }else{

         $output = [
            'success' => false,
            'message' => __('Granting authorization has failed, please check if the credentials are correct.', 'convesiopay-woocommerce'),
         ];
      }

      return $output;
   }

}