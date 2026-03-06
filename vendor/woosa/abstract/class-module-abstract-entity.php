<?php
/**
 * Module Abstract Entity
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


abstract class Module_Abstract_Entity{


   /**
    * The entity id.
    *
    * @var integer
    */
   protected $id = 0;


   /**
    * The entity data.
    *
    * @var array
    */
   protected $data = [];


   /**
    * List of event types.
    *
    * @var array
    */
   protected $event_types = [];



   /*
   |--------------------------------------------------------------------------
   | ABSTRACTS
   |--------------------------------------------------------------------------
   */

   /**
    * List of metadata keys to be used when identify the entity id.
    *
    * @return array
    */
   abstract protected function meta_key_identifiers();



   /*
   |--------------------------------------------------------------------------
   | SETTERS
   |--------------------------------------------------------------------------
   */

   /**
    * Sets the entity id.
    *
    * @param int $id
    * @return void
    */
   public function set_id(int $id){
      $this->id = $this->data['id'] = $id;
   }



   /**
    * Sets the entity data.
    *
    * @param array $data
    * @return void
    */
   public function set_data(array $data){
      $this->data = $data;
   }



   /**
    * Sets the event types.
    *
    * @param string $event_type
    * @return void
    */
   protected function set_event_type(string $event_type){

      $event_types       = array_filter((array) $event_type);
      $this->event_types = array_merge($this->event_types, $event_types);
   }



   /*
   |--------------------------------------------------------------------------
   | GETTERS
   |--------------------------------------------------------------------------
   */

   /**
    * Retrieves the entity id.
    *
    * @return int
    */
   public function get_id(){
      return $this->id;
   }



   /**
    * Retrieves the entity data.
    *
    * @return array
    */
   public function get_data(){
      return $this->data;
   }



   /**
    * Retrieves the event types.
    *
    * @return array
    */
   public function get_event_types(){
      return $this->event_types;
   }
}