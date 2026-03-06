<?php
/**
 * Module Core Hook
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;

use Automattic\WooCommerce\Utilities\FeaturesUtil;

//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Module_Core_Hook implements Interface_Hook{


   /**
    * Initiates the hooks.
    *
    * @return void
    */
   public static function init(){

      Module_Core_State::register_activation_hook();
      Module_Core_State::register_deactivation_hook();
      Module_Core_State::register_uninstall_hook();
      Module_Core_State::register_upgrade_hook();

      Module_Core_Hook_Assets::init();

      add_action('plugins_loaded', [__CLASS__, 'run'], 5);

      add_action('init', [__CLASS__, 'check_instance']);

      add_action('plugins_loaded', [__CLASS__, 'load_textdomain']);

      add_action('admin_init', [__CLASS__, 'init_plugin_action_links']);

      add_filter('is_protected_meta', [__CLASS__, 'hide_metadata_entries'], 10, 3);

      add_action('before_woocommerce_init', [__CLASS__, 'declare_HPOS_compatibility']);

   }



   /**
    * Runs the logic.
    *
    * @return void
    */
   public static function run(){

      Module_Core::pre_run();

      // Remove '$inititate = apply_filters(PREFIX . '\core\inititate', true);' call and conditional 'show_notice()' logic as it was throwing an error about loading WC translations too early
      Module_Core::init_modules();
   }



   /**
    * Checks the integrity of the instance.
    *
    * @return int|false
    */
   public static function check_instance(){

      $last_check = Transient::get('instance:last_check');

      if(empty($last_check)){

         $instance = Option::get('instance', []);

         if(empty($instance)){

            Module_Core::set_instance();

         }else{

            //make sure all filters are removed
            remove_all_filters( 'home_url' );

            $url    = untrailingslashit(home_url('/', 'https'));
            $domain = parse_url(home_url(), PHP_URL_HOST);

            if($instance['url'] != $url || $instance['domain'] != $domain){
               do_action(PREFIX . '\core\invalid_instance', $instance, $url, $domain);
            }

            Transient::set('instance:last_check', time(), \DAY_IN_SECONDS);
         }
      }

      return $last_check;
   }



   /**
    * Loads translation locale.
    *
    * @return void
    */
   public static function load_textdomain(){

      $path = DIR_PATH . '/languages/convesiopay-woocommerce-' . get_user_locale() . '.mo';

      if(file_exists($path)){

         $loaded = load_textdomain( 'convesiopay-woocommerce', $path );

         if( ! $loaded ){
            Util::log()->error([
               'message' => 'Translation could not be loaded.',
               'locale' => get_user_locale(),
               'path' => DIR_PATH . '/languages/convesiopay-woocommerce-' . get_user_locale() . '.mo',
            ]);
         }
      }
   }



   /**
    * Displays plugin action links in plugins list page.
    *
    * @return void
    */
   public static function init_plugin_action_links(){

      self::add_plugin_action_links( apply_filters( PREFIX . '\core\plugin_action_links', [
         'actions' => [],
         'meta' => [],
      ]));
   }



   /**
    * Adds plugin action and meta links.
    *
    * @param array $sections
    * @return void
    */
   protected static function add_plugin_action_links($sections = array()) {

      //actions
      if(isset($sections['actions'])){

         $actions = $sections['actions'];
         $links_hook = 'plugin_action_links_';

         add_filter($links_hook.DIR_BASENAME, function($links) use ($actions){

            foreach(array_reverse($actions) as $url => $label){
               $link = '<a href="'.$url.'">'.$label.'</a>';
               array_unshift($links, $link);
            }

            return $links;

         });
      }

      //meta row
      if(isset($sections['meta'])){

         $meta = $sections['meta'];

         add_filter( 'plugin_row_meta', function($links, $file) use ($meta){

            if(DIR_BASENAME == $file){

               foreach($meta as $url => $label){
                  $link = '<a href="'.$url.'">'.$label.'</a>';
                  array_push($links, $link);
               }
            }

            return $links;

         }, 10, 2 );
      }

   }



   /**
    * Hides our metadata entries but shows them if debug mode is enabled
    *
    * @param bool $protected
    * @param string $meta_key
    * @param string $meta_type
    * @return bool
    */
   public static function hide_metadata_entries($protected, $meta_key, $meta_type){

      if(strpos($meta_key, PREFIX.'_') !== false && DEBUG === false){
         $protected = true;
      }

      if(strpos($meta_key, '_' . PREFIX.'_') !== false && DEBUG === true){
         $protected = false;
      }

      $special_keys = [];

      if(class_exists(__NAMESPACE__ . '\\Module_Meta_Util')){
         $special_keys = array_merge(
            Module_Meta_Util::product_status_meta(),
            Module_Meta_Util::product_error_meta(),
            Module_Meta_Util::order_status_meta(),
            Module_Meta_Util::order_error_meta()
         );
      }

      if(in_array($meta_key, $special_keys)){
         $protected = true;
      }

      return $protected;
   }



   /**
    * Declares the plugin as compatible with HPOS.
    *
    * @return void
    */
   public static function declare_HPOS_compatibility() {

      if( class_exists( FeaturesUtil::class ) ){
         FeaturesUtil::declare_compatibility( 'custom_order_tables', DIR_BASENAME, true );
      }

   }

}