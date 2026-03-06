<?php
/**
 * Module Intercom Chat Hook Assets
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Module_Intercom_Chat_Hook_Assets implements Interface_Hook_Assets{


   /**
    * Initiates the hooks.
    *
    * @return void
    */
   public static function init(){

      add_action('admin_enqueue_scripts', [__CLASS__, 'admin_assets']);

   }



   /**
    * Enqueues public CSS/JS files.
    *
    * @return void
    */
   public static function public_assets(){}



   /**
    * Enqueues admin CSS/JS files.
    *
    * @return void
    */
   public static function admin_assets(){

      $screen = get_current_screen();

      if (empty($screen)) {
         return;
      }

      if(
         (
            in_array($screen->id, ['toplevel_page_' . SETTINGS_TAB_ID, 'bol_invoice_page_bol-settings'])
         ) ||
         (//this is DEPRECATED since Settings v2
            $screen->id === "woocommerce_page_wc-settings"
            && Util::array($_GET)->get('tab') === SETTINGS_TAB_ID
         )
      ) {

         Util::enqueue_scripts([
            [
               'name' => 'module-intercom-chat',
               'js' => [
                  'path' => untrailingslashit(plugin_dir_url(__FILE__)) . '/assets/js/',
                  'dependency' => ['jquery'],
                  'localize' => [
                     'appId' => Module_Intercom_Chat::get_app_id(),
                     'apiBase' => Module_Intercom_Chat::get_api_base(),
                     'userEmail' => Module_Intercom_Chat::get_user_email(),
                  ],
               ],
            ],
         ]);

      }

   }
}
