<?php
/**
 * Module Abstract Entity Post
 *
 * Payload structure:
 * [
 *    'id'                => 0,
 *    'name'              => '',
 *    'type'              => ''
 *    'status'            => ''
 *    'description'       => '',
 *    'short_description' => '',
 *    'parent_id'         => 0,
 *    'meta_data'         => []
 * ]
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


abstract class Module_Abstract_Entity_Post extends Module_Abstract_Entity{


   /**
    * The list of supported types.
    *
    * @var array
    */
   protected $supported_types = ['post'];



   /**
    * Checks whether or not the type is supported.
    *
    * @return boolean
    */
   public function is_supported_type(){

      $result  = false;
      $type    = Util::array($this->data)->get('type');

      if(in_array($type, $this->supported_types)){
         $result = true;
      }

      return $result;
   }



   /**
    * Retrieves entity id either by a metadata key or `id` property.
    *
    * $this->data = [
    *    'id' => 123
    * ]
    * --- OR ---
    * $this->data = [
    *    'meta_data' => [
    *       'meta_key' => '123',
    *    ]
    * ]
    *
    * @return int
    */
   public function get_id_from_data(){

      global $wpdb;

      $id = empty($this->id) ? Util::array($this->data)->get('id', 0) : $this->id;

      if(empty($id) && $this->data){

         $where = [];

         foreach($this->meta_key_identifiers() as $key){

            $value = Util::array($this->data)->get("meta_data/{$key}");

            if( ! empty($value) ){
               $where[] = "(meta_key = '{$key}' AND meta_value = '{$value}')";
            }
         }

         $where = implode(' OR ', $where);

         if( ! empty($where) ){
            $id = $wpdb->get_var("SELECT post_id FROM $wpdb->postmeta WHERE {$where} LIMIT 1");
         }

      }

      return (int) $id;
   }



   /**
    * Creates a new post. At success the post id will be set to `$id`.
    *
    * $this->data = [
    *    'name'              => '',
    *    'type'              => '',
    *    'status'            => '',
    *    'description'       => '',
    *    'short_description' => '',
    *    'parent_id'         => 0,
    * ]
    *
    * @return void
    */
   public function create(){

      global $wpdb;

      if($this->data){

         $columns = apply_filters(PREFIX . '\abstract\entity_post\create\columns', [
            'post_author'           => get_current_user_id(),
            'post_date'             => date('Y-m-d H:i:s', time()),
            'post_date_gmt'         => gmdate('Y-m-d H:i:s'),
            'post_content'          => Util::array($this->data)->get_post_content('description', ' '),
            'post_excerpt'          => Util::array($this->data)->get_post_content('short_description', ''),
            'post_title'            => Util::array($this->data)->get('name'),
            'post_status'           => Util::array($this->data)->get('status'),
            'post_name'             => sanitize_title( Util::array($this->data)->get('name') ),
            'post_parent'           => Util::array($this->data)->get('parent_id', '0'),
            'post_type'             => Util::array($this->data)->get('type'),
            'comment_status'        => 'closed',
            'ping_status'           => 'closed',
            'post_password'         => '',
            'to_ping'               => '',
            'pinged'                => '',
            'post_modified'         => '',
            'post_modified_gmt'     => '',
            'post_content_filtered' => '',
            'guid'                  => '',
            'post_mime_type'        => '',
            'menu_order'            => 0,
            'comment_count'         => 0,
         ], $this);

         if( empty($columns['post_title']) || empty($columns['post_type']) || empty($columns['post_status'])){

            Util::wc_error_log([
               'error' => [
                  'message' => 'Creating post failed. The payload is invalid. The following properties should provided and not empty: `post_title`, `post_type` and `post_status`.',
                  'data'    => $this->data,
                  'columns' => $columns,
               ],
            ], __FILE__, __LINE__);

         }else{

            $inserted = $wpdb->insert($wpdb->posts, $columns);

            if($inserted){

               $this->id = (int) $wpdb->insert_id;
               $post_name     = wp_unique_post_slug( $columns['post_name'], $this->id, $columns['post_status'], $columns['post_type'], $columns['post_name'] );

               //make sure it has unique slug
               $wpdb->update(
                  $wpdb->posts,
                  [
                     'post_name' => $post_name,
                     'guid'      => home_url("?p={$this->id}")
                  ],
                  [
                     'ID' => $this->id
                  ]
               );

               $this->set_event_type('post.created');

            }else{

               Util::wc_error_log([
                  'error' => [
                     'message' => $wpdb->last_error
                  ],
                  'detail' => [
                     'query' => $wpdb->last_query
                  ],
               ], __FILE__, __LINE__);
            }
         }
      }

   }



   /**
    * Creates postmeta entries.
    *
    * $this->data = [
    *    'meta_data' => []
    * ]
    *
    * @return void
    */
   public function create_metadata(){

      if($this->id && $this->data){

         $created = 0;
         $entries = apply_filters(PREFIX . '\abstract\entity_post\create_metadata\entries', Util::array($this->data)->get('meta_data', []), $this);

         //add some defaults
         $entries = array_merge($entries, [
            Util::prefix('plugin_version') => VERSION,
         ]);

         foreach($entries as $key => $value){
            if(add_post_meta($this->id, $key, $value, true)){
               $created++;
            }
         }

         if($created > 0){
            $this->set_event_type('postmeta.created');
         }
      }

   }



   /**
    * Updates a post.
    *
    * $this->data = [
    *    'name'              => '',
    *    'status'            => '',
    *    'description'       => '',
    *    'short_description' => '',
    * ]
    *
    * @return void
    */
   public function update(){

      global $wpdb;

      if($this->id && $this->data){

         $columns = [];

         if(array_key_exists('name', $this->data)){
            $columns['post_title'] = $this->data['name'];
            $columns['post_name']  = sanitize_title($this->data['name']);
         }

         if(array_key_exists('status', $this->data)){
            $columns['post_status'] = $this->data['status'];
         }

         if(array_key_exists('description', $this->data)){
            $columns['post_content'] = Util::array($this->data)->get_post_content('description');
         }

         if(array_key_exists('short_description', $this->data)){
            $columns['post_excerpt'] = Util::array($this->data)->get_post_content('short_description');
         }

         $columns = apply_filters(PREFIX . '\abstract\entity_post\update\columns', $columns, $this);

         if( ! empty($columns) ){

            $updated = $wpdb->update(
               $wpdb->posts,
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

               //set the modified date
               $wpdb->update(
                  $wpdb->posts,
                  [
                     'post_modified'     => date('Y-m-d H:i:s', time()),
                     'post_modified_gmt' => gmdate('Y-m-d H:i:s'),
                  ],
                  [
                     'ID' => $this->id
                  ]
               );

               $this->set_event_type('post.updated');
            }
         }
      }

   }



   /**
    * Updates (or adds) postmeta entries.
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
         $entries = apply_filters(PREFIX . '\abstract\entity_post\update_metadata\entries', Util::array($this->data)->get('meta_data', []), $this);

         foreach($entries as $key => $value){
            if(update_post_meta($this->id, $key, $value)){
               $updated++;
            }
         }

         if($updated > 0){
            $this->set_event_type('postmeta.updated');
         }
      }

   }



   /**
    * Deletes a post.
    *
    * @return void
    */
   public function delete(){

      if($this->id){

         $deleted = wp_delete_post($this->id, true);

         if($deleted){
            $this->set_event_type('post.deleted');
         }

      }

   }



   /**
    * Trashes a post.
    *
    * @return void
    */
   public function trash(){

      if($this->id){

         $trashed = wp_trash_post($this->id);

         if($trashed){
            $this->set_event_type('post.trashed');
         }

      }

   }



   /**
    * Whether or not to move the post to trash instead to delete it completely.
    *
    * @return bool
    */
   public function move_to_trash(){
      return apply_filters(PREFIX . '\abstract\entity_post\move_to_trash', false, $this);
   }



   /**
    * Retrieves the post children ids.
    *
    * @return array
    */
   public function get_children(){

      global $wpdb;

      $results = [];
      $query   = $wpdb->get_results("SELECT ID FROM {$wpdb->posts} WHERE post_parent = '{$this->id}' ORDER BY ID ASC");

      foreach($query as $entry){
         $results[] = $entry->ID;
      }

      return $results;
   }
}