<?php
/**
 * Module License Hook
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Module_License_Hook {


   /**
    * Initiates the hooks.
    *
    * @return void
    */
   public static function init(){

      add_action(PREFIX . '\module\settings\page\top', [__CLASS__, 'show_warning']);

      add_filter(PREFIX . '\logger\criteria_list', [__CLASS__, 'show_warning_in_logs']);

   }



   /**
    * Display warning message in the page of settings.
    *
    * @param array $current_tab
    * @return string
    */
   public static function show_warning($current_tab){

      //skip Logs tab
      if('logs' === Util::array($current_tab)->get('slug') ){
         return;
      }

      $ml = new Module_License();

      if( ! $ml->is_active() ):?>

         <div class="mb-20 alertbox alertbox--yellow">
            <?php echo self::get_warning_message();?>
         </div>

      <?php endif;
   }



   /**
    * Get the warning message.
    *
    * @return string
    */
   protected static function get_warning_message() {
      return Util::get_template('warning-message.php', [], dirname(dirname(__FILE__)), untrailingslashit(basename(dirname(__FILE__))) . '/templates');
   }



   /**
    * Displays the warning in Logs.
    *
    * @param array $items
    * @return array
    */
   public static function show_warning_in_logs($items){

      $ml = new Module_License();

      $items['license_key_warning'] = [
         'type'    => 'warning',
         'message' => self::get_warning_message(),
         'hook'    => 'admin_init',
         'active'  => ! $ml->is_active(),
      ];

      return $items;
   }


}
