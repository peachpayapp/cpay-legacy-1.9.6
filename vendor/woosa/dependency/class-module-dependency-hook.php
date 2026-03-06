<?php
/**
 * Module Dependency Hook
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Module_Dependency_Hook implements Interface_Hook{


   /**
    * Initiates the hooks.
    *
    * @return void
    */
   public static function init(){

      add_filter(PREFIX . '\core\inititate', [__CLASS__, 'check_on_init']);
      add_filter(PREFIX . '\core\activate', [__CLASS__, 'check_on_activate']);

      add_filter(PREFIX . '\logger\criteria_list', [__CLASS__, 'add_in_log_criteria_list']);
   }



   /**
    * Perform a full check before initiation.
    *
    * @param bool|array $output
    * @return bool|array
    */
   public static function check_on_init($output){
      return Module_Dependency::is_check_passed($output);
   }



   /**
    * Perform a full check before activation.
    *
    * @param bool|array $output
    * @return bool|array
    */
   public static function check_on_activate($output){
      return Module_Dependency::is_check_passed($output, true);
   }



   /**
    * Insert log criteria for authorization access.
    *
    * @param array $items
    * @return array
    */
   public static function add_in_log_criteria_list($items){

      $items['max_exec_time'] = [
         'type'    => 'warning',
         'message' => sprintf(__('Your server %s should be at least %s seconds! Please get in touch with your hosting provider to increase that otherwise the plugin might not work properly.', 'convesiopay-woocommerce'), '<code>max_execution_time</code>', Module_Dependency::max_exec_time()),
         'hook'    => 'admin_init',
         'active'  => Module_Dependency::require_max_exec_time(),
      ];

      return $items;
   }
}