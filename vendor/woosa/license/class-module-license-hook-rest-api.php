<?php
/**
 * Module License Hook REST API
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Module_License_Hook_REST_API implements Interface_Hook{


   /**
    * Initiates the hooks.
    *
    * @return void
    */
   public static function init(){

      add_action('rest_api_init', [ __CLASS__, 'register_endpoints' ]);

   }



   /**
    * Registers endpoints.
    *
    * @return void
    */
   public static function register_endpoints() {

      register_rest_route(
         DIR_NAME . '/v1',
         '/software/update',
         [
            'methods' => 'POST',
            'callback' => [ __CLASS__, 'process_software_update_request' ],
            'permission_callback' => '__return_true',
         ]
      );
   }



   /**
    * Processes the requests received on `/software/update` endpoint.
    *
    * @param WP_REST_Request $request
    * @return \WP_REST_Response
    */
   public static function process_software_update_request( $request ) {

      $license = new Module_License;

      if($request->get_header('x-license-key') === $license->key){

         if($request->has_param('version') && $request->has_param('url') && $request->has_param('file')){

            $response = new \WP_REST_Response( [], 204 );

            $license->cache_update( $request->get_params() );

            if(DEBUG){
               Util::wc_debug_log([
                  'method' => $request->get_method(),
                  'body' => $request->get_params(),
                  'headers' => $request->get_headers(),
               ], __FILE__, __LINE__ );
            }

         }else{

            $response = new \WP_Error( 'bad_request', 'The request payload must contain at least `version`, `url` and `file`.', [ 'status' => 400 ] );

         }

      }else{

         $response = new \WP_Error( 'invalid_license_key', 'The license key is invalid.', [ 'status' => 403 ] );
      }

      return $response;
   }

}