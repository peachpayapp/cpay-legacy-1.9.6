<?php
/**
 * Module Tools Hook AJAX
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Module_Tools_Hook_AJAX implements Interface_Hook{


   /**
    * Initiates the hooks.
    *
    * @return void
    */
   public static function init(){

      add_action('wp_ajax_' . PREFIX . '_run_tool', [__CLASS__, 'handle_run_tool']);

   }



   /**
    * Processes the request to run the tool.
    *
    * @return string
    */
   public static function handle_run_tool(){

      //check to make sure the request is from same server
      if(!check_ajax_referer( 'wsa-nonce', 'security', false )){
         return;
      }

      $id = Util::array($_POST)->get('tool_id');

      if('clear_cache' === $id){
         Module_Tools::clear_cache();
      }

      if('allow_long_run_requests' === $id){

         $allow_lrr = Option::get('allow_long_run_requests', 0);

         Option::set('allow_long_run_requests', ! $allow_lrr);

         if ( ! function_exists( 'save_mod_rewrite_rules' ) ) {
            require_once ABSPATH . 'wp-admin/includes/misc.php';
         }

         //make sure the .htaccess file is regenerated to include our rewrite rules
         save_mod_rewrite_rules();

         wp_send_json_success([
            'reload' => true,
         ]);
      }

      do_action_deprecated(PREFIX . '\tools\run_tool', [$id], '2.1.0', PREFIX . '\module\tools\run_tool');

      do_action(PREFIX . '\module\tools\run_tool', $id);

      wp_send_json_success([
         'message' => __('The tool has been performed successfully.', 'convesiopay-woocommerce'),
      ]);

   }
}