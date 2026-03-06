<?php
/**
 * Module Authorization Hook
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Module_Authorization_Hook implements Interface_Hook{


   /**
    * Initiates the hooks.
    *
    * @return void
    */
   public static function init(){

      add_filter(PREFIX . '\action_bulker\initiate', [__CLASS__, 'is_access_granted']);
      add_filter(PREFIX . '\action_bulker\allow_perform', [__CLASS__, 'is_access_granted']);

      add_filter(PREFIX . '\table_column\initiate', [__CLASS__, 'is_access_granted']);

      add_filter(PREFIX . '\category_mapping\initiate', [__CLASS__, 'is_access_granted']);

      add_filter(PREFIX . '\dropshipping\product_filter\initiate', [__CLASS__, 'is_access_granted']);

      add_filter(PREFIX . '\module\synchronization\initiate', [__CLASS__, 'is_access_granted']);

      add_filter(PREFIX . '\heartbeat\initiate', [__CLASS__, 'is_access_granted']);

      add_filter(PREFIX . '\validation\disabled_field', [__CLASS__, 'is_access_granted']);

      add_filter(PREFIX . '\logger\criteria_list', [__CLASS__, 'add_in_log_criteria_list']);

      add_action(PREFIX . '\request\sent', [__CLASS__, 'set_as_unauthorized'], 10, 2);
   }



   /**
    * Whether or not the access is granted.
    *
    * @param bool $bool
    * @return boolean
    */
   public static function is_access_granted($bool){

      $ma = new Module_Authorization();

      if( ! $ma->is_authorized() ){
         $bool = false;
      }

      return $bool;
   }



   /**
    * Insert log criteria for authorization access.
    *
    * @param array $items
    * @return array
    */
   public static function add_in_log_criteria_list($items){

      $ma = new Module_Authorization();

      $items['not_authorized'] = [
         'type'    => 'warning',
         'message' => sprintf(__('You have to authorize the plugin. Please go to %sthis page%s.', 'convesiopay-woocommerce'), '<a href="'.SETTINGS_URL.'&section=authorization">', '</a>'),
         'hook'    => 'admin_init',
         'active'  => ! $ma->is_authorized(),
      ];

      return $items;
   }



   /**
    * Sets as unauthorized if the remote request is flagged as `authorized` but it gets 401 status.
    *
    * @param object $response
    * @param Request $request
    * @return void
    */
   public static function set_as_unauthorized($response, $request){

      if(Util::array($request->get_args())->get('authorized', false)){

         $ma = new Module_Authorization();

         if( $ma->is_authorized() && 401 == $response->status){
            $ma->set_as_unauthorized();
         }
      }

   }

}