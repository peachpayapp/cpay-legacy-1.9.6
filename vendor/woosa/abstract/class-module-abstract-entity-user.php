<?php
/**
 * Module Abstract Entity User
 *
 * Payload structure:
 * [
 *    'id'        => 0,
 *    'username'  => '',
 *    'password'  => '',
 *    'nickname'  => '',
 *    'email'     => '',
 *    'meta_data' => [
 *       'first_name'    => '',
 *       'last_name'     => '',
 *       '{prefix}_role' => '',
 *    ],
 * ]
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


abstract class Module_Abstract_Entity_User extends Module_Abstract_Entity{


   /**
    * List of metadata keys to be used when identify the entity id.
    *
    * @return array
    */
   protected function meta_key_identifiers(){}



   /**
    * Retrieves user id either by `email` or `id` property.
    *
    * $this->data = [
    *    'email' => 'test@testmail.com'
    * ]
    *
    * @return int
    */
   public function get_id_from_data(){

      global $wpdb;

      $id = empty($this->id) ? Util::array($this->data)->get('id', 0) : $this->id;

      if(empty($id) && $this->data){

         $email = Util::array($this->data)->get('email');

         if( ! empty($email) ){
            $id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->users WHERE user_email = '%s' LIMIT 1", $email ) );
         }

      }

      return (int) $id;

   }



   /**
    * Creates a new user. At success the user id will be set to `$id`.
    *
    * $this->data = [
    *    'username'  => '',
    *    'password'  => '',
    *    'nickname'  => '',
    *    'email'     => '',
    *    'meta_data' =>  [
    *       'first_name'    => '',
    *       'last_name'     => '',
    *       '{prefix}_role' => '',
    *    ]
    * ]
    *
    * @return void
    */
   public function create(){

      if($this->data){

         $columns = apply_filters(PREFIX . '\abstract\entity_user\create\columns', [
            'user_login'      => Util::array($this->data)->get('username'),
            'user_pass'       => Util::array($this->data)->get('password'),
            'user_nicename'   => Util::array($this->data)->get('nickname'),
            'user_email'      => Util::array($this->data)->get('email'),
            'user_registered' => date('Y-m-d H:i:s', time()),
            'first_name'      => Util::array($this->data)->get('meta_data/first_name'),
            'last_name'       => Util::array($this->data)->get('meta_data/last_name'),
            'role'            => Util::array($this->data)->get('meta_data/' . Util::prefix('role'), 'subscriber'),
         ], $this->data, $this);

         if( empty($columns['user_login']) || empty($columns['user_pass']) || empty($columns['user_email'])){

            Util::wc_error_log([
               'error' => [
                  'message' => 'Creating user failed. The payload is invalid. The following properties should provided and not empty: `user_login`, `user_pass` and `user_email`.',
                  'data'    => $this->data,
                  'columns' => $columns,
               ],
            ], __FILE__, __LINE__);

         }else{

            $user_id = wp_insert_user($columns);

            if(is_wp_error($user_id)){

               Util::wc_error_log([
                  'error' => [
                     'code' => $user_id->get_error_code(),
                     'message' => $user_id->get_error_message(),
                  ],
                  'detail' => [
                     'user' => $columns
                  ],
               ], __FILE__, __LINE__);

            }else{

               $this->id = (int) $user_id;

               $this->set_event_type('user.created');
            }
         }
      }

   }



   /**
    * Creates usermeta entries.
    *
    * $this->data = [
    *    'meta_data' => [],
    * ]
    *
    * @return void
    */
   public function create_metadata(){

      if($this->id && $this->data){

         $created = 0;
         $entries = apply_filters(PREFIX . '\abstract\entity_user\create_metadata\entries', Util::array($this->data)->get('meta_data', []), $this->data, $this);

         //add some defaults
         $entries = array_merge($entries, [
            Util::prefix('plugin_version') => VERSION,
         ]);

         foreach($entries as $key => $value){
            if(add_user_meta($this->id, $key, $value, true)){
               $created++;
            }
         }

         if($created > 0){
            $this->set_event_type('usermeta.created');
         }
      }
   }



   /**
    * Updates a user.
    *
    * $this->data = [
    *    'username' => '',
    *    'password' => '',
    *    'email'    => '',
    * ]
    *
    * @return void
    */
   public function update(){

      global $wpdb;

      if($this->id && $this->data){

         $columns = apply_filters(PREFIX . '\abstract\entity_user\update\columns', [
            'user_login' => Util::array($this->data)->get('username'),
            'user_pass'  => Util::array($this->data)->get('password'),
            'user_email' => Util::array($this->data)->get('email'),
         ], $this->data, $this);

         //remove empty entries
         $columns = array_filter($columns);

         $updated = $wpdb->update(
            $wpdb->users,
            $columns,
            [
               'ID' => $this->id
            ]
         );

         if($updated === false){

            Util::wc_error_log([
               'error' => [
                  'message' => $wpdb->last_error
               ],
               'detail' => [
                  'query' => $wpdb->last_query
               ],
            ], __FILE__, __LINE__);

         }

         if($updated > 0){
            $this->set_event_type('user.updated');
         }
      }

   }



   /**
    * Updates (or adds) usermeta entries.
    *
    * $this->data = [
    *    'meta_data' => []
    * ]
    *
    * @return void
    */
   public function update_metadata(){

      if($this->id && $this->data){

         $updated = 0;
         $entries = apply_filters(PREFIX . '\abstract\entity_user\update_metadata\entries', Util::array($this->data)->get('meta_data', []), $this->data, $this);

         foreach($entries as $key => $value){
            if(update_user_meta($this->id, $key, $value)){
               $updated++;
            }
         }

         if($updated > 0){
            $this->set_event_type('usermeta.updated');
         }
      }

   }



   /**
    * Deletes a user.
    *
    * @return void
    */
   public function delete(){

      if($this->id){

         if( ! function_exists('wp_delete_user') ){
            require_once(ABSPATH . 'wp-admin/includes/user.php');
         }

         $deleted = wp_delete_user($this->id);

         if($deleted){
            $this->set_event_type('user.deleted');
         }

      }

   }

}