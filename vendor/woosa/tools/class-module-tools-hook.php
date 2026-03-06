<?php
/**
 * Module Tools Hook
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Module_Tools_Hook implements Interface_Hook{


   /**
    * Initiates the hooks.
    *
    * @return void
    */
   public static function init(){

      add_filter('mod_rewrite_rules', [__CLASS__, 'add_long_run_rewrite_rule']);
   }



   /**
    * Adds rewrite rules to prevent timing out long-running requests.
    *
    * @param string $rules
    * @return string
    */
   public static function add_long_run_rewrite_rule($rules) {

      $enabled = Option::get('allow_long_run_requests', 0);

      if( ! $enabled ){
         return $rules;
      }

      $noabort_rule = "RewriteRule .* - [E=noabort:1] # prevent abort - added by Woosa\n";
      $noconntimeout_rule = "RewriteRule .* - [E=noconntimeout:1] # prevent timeout - added by Woosa\n";

      // Check if RewriteEngine is present
      if (strpos($rules, 'RewriteEngine On') !== false) {
         // Check if the noabort rule already exists
         if (strpos($rules, 'RewriteRule .* - [E=noabort:1]') === false) {
            $rules = preg_replace('/(RewriteEngine On[\r\n]+)/', '$1' . $noabort_rule, $rules);
         }

         // Check if the noconntimeout rule already exists
         if (strpos($rules, 'RewriteRule .* - [E=noconntimeout:1]') === false) {
            $rules = preg_replace('/(RewriteEngine On[\r\n]+)/', '$1' . $noconntimeout_rule, $rules);
         }
      }

      return $rules;
   }

}