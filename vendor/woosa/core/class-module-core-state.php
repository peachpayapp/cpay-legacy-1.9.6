<?php
/**
 * Module Core State
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Module_Core_State{


   /**
    * Registers the activation hook.
    *
    * @return void
    */
    public static function register_activation_hook(){
      register_activation_hook( dirname(DIR_PATH).'/'.DIR_BASENAME, [__CLASS__, 'activation'] );
   }



   /**
    * Registers the deactivation hook.
    *
    * @return void
    */
   public static function register_deactivation_hook(){
      register_deactivation_hook( dirname(DIR_PATH).'/'.DIR_BASENAME, [__CLASS__, 'deactivation'] );
   }



   /**
    * Registers the uninstall hook.
    *
    * @return void
    */
   public static function register_uninstall_hook(){
      register_uninstall_hook( dirname(DIR_PATH).'/'.DIR_BASENAME, [__CLASS__, 'uninstall'] );
   }



   /**
    * Registers the upgrade hook.
    *
    * @return void
    */
   public static function register_upgrade_hook(){
      add_action('upgrader_process_complete', [__CLASS__, 'upgrade'], 10, 2);
   }



   /**
    * Runs when plugin is activated.
    *
    * @return void
    */
   public static function activation(){

      Module_Core::pre_run();

      $activate = apply_filters(PREFIX . '\core\activate', true);

      if(isset($activate['error'])){

         wp_die($activate['error']);

      }else{

         Module_Core::init_modules();

         do_action(PREFIX . '\core\state\activated');
      }

   }



   /**
    * Runs when plugin is deactivated.
    *
    * @return void
    */
   public static function deactivation(){
      do_action(PREFIX . '\core\state\deactivated');
   }



   /**
    * Runs when plugin is updated.
    *
    * @param object $upgrader_object
    * @param array $options
    * @return void
    */
   public static function upgrade( $upgrader_object, $options ) {

      if(isset($options['plugins']) && $options['action'] == 'update' && $options['type'] == 'plugin' ){

         foreach($options['plugins'] as $plugin){

            if($plugin == DIR_BASENAME){
               do_action(PREFIX . '\core\state\upgraded');
            }
         }
      }
   }



   /**
    * Runs when plugin is deleted.
    *
    * @return void
    */
   public static function uninstall(){

      Module_Core::init_modules();

      do_action(PREFIX . '\core\state\uninstalled');
   }
}