<?php
/**
 * Interface Entity Task
 *
 * This interface is dedicated for processing an entity (e.g. Product, Order, User, etc.) via `Task` module.
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


interface Interface_Entity_Task{


   /**
    * List of supported actions.

    * @return array
    */
   public static function action_list();



   /**
    * Retrieves the entity id from the shop.
    *
    * @return string|int
    */
   public function get_id();



   /**
    * Retrieves the entity id from the service.
    *
    * @return string|int
    */
   public function get_remote_id();



   /**
    * Retrieves the entity type.
    *
    * @return string
    */
   public function get_type();



   /**
    * Checks whether or not the entity type is supported.
    *
    * @return boolean
    */
   public function is_supported_type();



   /**
    * Checks whether or not the entity is valid.
    *
    * @return boolean
    */
   public function is_valid();



   /**
    * Sets the entity errors.
    *
    * @param string $message
    * @param string $key - the key which the error message will be set in the list of errors.
    * @return void
    */
   public function set_error($message, $key = '');



   /**
    * Deletes the entity errors.
    *
    * @param string $key - the key which the error message will be set in the list of errors.
    * @return void
    */
   public function delete_error($key = '');



   /**
    * Sets the entity status.
    *
    * @param string $status
    * @return void
    */
   public function set_status($status);



   /**
    * Deletes the entity status.
    *
    * @return void
    */
   public function delete_status();



   /**
    * Sets the references between service & shop entity.
    *
    * @return void
    */
   public function set_reference();



   /**
    * Deletes the references between service & shop entity.
    *
    * @return void
    */
   public function delete_reference();



   /**
    * Creates the entity.
    *
    * @return void
    */
   public function create();



   /**
    * Updates the entity.
    *
    * @return void
    */
   public function update();



   /**
    * Deletes the entity.
    *
    * @return void
    */
   public function delete();

}