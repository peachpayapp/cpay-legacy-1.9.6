<?php
/**
 * Module License Hook Update
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Module_License_Hook_Update implements Interface_Hook{


   /**
    * Initiates the hooks.
    *
    * @return void
    */
   public static function init(){

      add_filter('transient_update_plugins', [__CLASS__, 'show_update_notification']);
      add_filter('site_transient_update_plugins', [__CLASS__, 'show_update_notification']);

      add_filter('plugins_api', [__CLASS__, 'plugin_info'], 10, 3);

      add_filter('upgrader_pre_download', [__CLASS__, 'before_download_package'], 99, 3);

   }



   /**
    * Shows the update notification.
    *
    * @param array $plugins
    * @return array
    */
   public static function show_update_notification( $plugins ) {

      if ( is_object( $plugins ) ) {

         if ( self::is_available() ) {

            $license      = new Module_License;
            $info         = $license->get_software_info();
            $requires     = '';
            $requires_php = '';

            if ( ! isset( $plugins->response ) || ! is_array( $plugins->response ) ) {
               $plugins->response = [];
            }

            if(isset($info->requirements)){
               $requires = $info->requirements->wordpress;
               $requires_php = $info->requirements->php;
            }

            $plugins->response[DIR_BASENAME] = (object) [
               'slug'         => DIR_NAME,
               'plugin'       => DIR_BASENAME,
               'new_version'  => $info->version,
               'url'          => $info->url,
               'package'      => $info->file,
               'requires'     => $requires,
               'requires_php' => $requires_php,
            ];

         }

      }

      return $plugins;
   }



   /**
    * Overwrites the Wordpres API response with the plugin custom info.
    *
    * @param false|object $res
    * @param string $action
    * @param object|array $args
    * @return false|object
    */
   public static function plugin_info($res, $action, $args){

      if('plugin_information' === $action && DIR_NAME === $args->slug){

         $license      = new Module_License;
         $info         = $license->get_software_info();
         $author       = '';
         $description  = '';
         $changelog    = '';
         $requires     = '';
         $requires_php = '';
         $last_updated = '';

         if(isset($info->author)){
            $author = $info->author;
         }

         if(isset($info->changelog)){
            $changelog = $info->changelog;
         }elseif(isset($info->sections->changelog)){
            $changelog = $info->sections->changelog;
         }

         if(isset($info->short_description)){
            $description = $info->short_description;
         }

         if(isset($info->requirements)){
            $requires = $info->requirements->wordpress;
            $requires_php = $info->requirements->php;
         }

         if(isset($info->last_release)){
            $last_updated = $info->last_release;
         }

         $res = apply_filters(PREFIX . '\license\plugin_info', (object)[
            'name'                     => NAME,
            'slug'                     => DIR_NAME,
            'version'                  => $info->version,
            'author'                   => $author,
            'author_profile'           => '',
            'contributors'             => [],
            'requires'                 => $requires,
            'tested'                   => '',
            'requires_php'             => $requires_php,
            'rating'                   => 0,
            'ratings'                  => [],
            'num_ratings'              => 0,
            'support_threads'          => 0,
            'support_threads_resolved' => 0,
            // 'active_installs'       => 0,
            'last_updated'             => $last_updated,
            'added'                    => '',
            'homepage'                 => $info->url,
            'sections'                 => [
               'description' => $description,
               // 'faq'         => '',
               'changelog'   => $changelog,
               // 'screenshots' => '',
               // 'reviews'     => '',
            ],
            'download_link' => $info->url,
            'screenshots'   => '',
            'tags'          => [],
            'versions'      => [],
            'donate_link'   => [],
            'banners'       => [
               'low' => '',
               'high' => untrailingslashit(plugin_dir_url(__FILE__)) . '/assets/images/banner-woosa.jpg',
            ],
         ]);
      }

      return $res;
   }



   /**
    * Performs a extra check before downloading the file.
    *
    * @param boolean $reply
    * @param array $package
    * @param object $upgrader
    * @return string
    */
   public static function before_download_package( $reply, $package, $upgrader ) {

      if ( $package == DIR_NAME . '.zip' ) {

         $upgrader->skin->feedback( 'downloading_package', $package );

         $response = self::get_download_access();

         if( isset($response->access_token) ) {

            $reply = self::download_package( $response->access_token );

         } else {

            $reply = new \WP_Error( 'invalid_access_token', 'Invalid access token. The file could not be downloaded.' );
         }
      }

      return $reply;

   }



   /**
    * Retrieves the access for downloading the package.
    *
    * @return object
    */
   protected static function get_download_access() {

      $license  = new Module_License;
      $response = Request::POST([
         'headers' => $license->headers(),
         'timeout' => 120,
         'body'    => json_encode([
            'key'      => $license->key,
            'package'  => $license->package,
            'site_url' => $license->site_url,
         ])
      ])->send( $license->get_api_url('software/update/prepare') );

      $result = $response->body;

      return $result;

   }



   /**
    * Downloads the package file.
    *
    * @param string $access_token
    * @return string
    */
   protected static function download_package( $access_token ) {

      $result    = false;
      $file_name = DIR_NAME . '.zip';
      $tmpfname  = wp_tempnam( $file_name );

      if ( ! $tmpfname ) {
         $result = new \WP_Error( 'http_no_file', __( 'Could not create Temporary file.', 'convesiopay-woocommerce' ) );
      }

      $license = new Module_License;
      $response = Request::GET([
         'timeout'      => 30,
         'stream'       => true,
         'filename'     => $tmpfname,
         'query_params' => [
            'key'          => $license->key,
            'access_token' => $access_token,
         ]
      ])->send( $license->get_api_url('software/update/download') );

      if($response->status == 200){

         if ( filesize( $tmpfname ) > 0 ) {
            $result = $tmpfname;
         } else {
            $content = wp_remote_retrieve_body( $response );
            $fs = new \WP_Filesystem_Direct(false);
            $fs->put_contents( $tmpfname, $content );
            $result = $tmpfname;
         }

      }else{

         $result = new \WP_Error( 'http_404', trim( wp_remote_retrieve_response_message( $response ) ) );
      }

      return $result;

   }



   /**
    * Checks whether or not an update is available.
    *
    * @return boolean $response
    */
   public static function is_available() {

      $result = false;
      $license = new Module_License;

      if ( $license->is_active() ) {

         $info = $license->get_software_info();

         if ( isset($info->version) && version_compare( $info->version, VERSION, ">" ) ) {
            $result = true;
         }
      }

      return apply_filters(PREFIX . '\license\is_update_available', $result, $license);
   }

}