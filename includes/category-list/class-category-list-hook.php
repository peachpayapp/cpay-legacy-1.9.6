<?php
/**
 * Category List Hook
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Category_List_Hook implements Interface_Hook{


   /**
    * Initiates the hooks.
    *
    * @return void
    */
   public static function init(){

      add_filter(PREFIX . '\category_selection\service_items', [__CLASS__, 'get_stores']);

   }



   /**
    * Get the Adyen stores
    *
    * @return array
    */
   public static function get_stores($items) {

      $stores = Service::management()->get_stores();

      if (!empty($stores)) {
         $map_stores = [];

         foreach ($stores as $store) {

            $store_data = Util::obj_to_arr($store);

            $map_stores[] = [
               'id' => Util::array($store_data)->get('id'),
               'name' => Util::array($store_data)->get('description', Util::array($store_data)->get('reference')),
               'parent_id' => 0,
            ];

         }

         return array_merge($items, $map_stores);

      }

      return $items;

   }


}
