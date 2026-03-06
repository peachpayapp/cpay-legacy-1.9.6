<?php
/**
 * Interface Hook Order Details
 *
 * This interface is dedicated for processing the order items via `Order-Details` module hooks.
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


interface Interface_Hook_Order_Details{


   /**
    * Initiates the hooks.
    *
    * @return void
    */
   public static function init();



   /**
    * Defines the list of shipping carriers.
    *
    * @param array $list
    * @param \WC_Order $order
    * @return array
    */
   public static function define_ship_carriers(array $list, \WC_Order $order);



   /**
    * Defines the list of cancel reasons.
    *
    * @param array $list
    * @param \WC_Order $order
    * @return array
    */
   public static function define_cancel_reasons(array $list, \WC_Order $order);



   /**
    * Processes the check of delivery options.
    *
    * @param array $fields
    * @param \WC_Order $order
    * @return void|string
    */
   public static function handle_check_delivery_options(array $fields, \WC_Order $order);



   /**
    * Processes the ship of order items.
    *
    * @param array $fields
    * @param \WC_Order $order
    * @return void|string
    */
   public static function handle_ship_order_items(array $fields, \WC_Order $order);



   /**
    * Processes the cancellation of order items.
    *
    * @param array $fields
    * @param \WC_Order $order
    * @return void|string
    */
   public static function handle_cancel_order_items(array $fields, \WC_Order $order);

}