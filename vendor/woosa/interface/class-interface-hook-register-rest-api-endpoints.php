<?php
/**
 * Interface Hook Register REST API Endpoints
 *
 * This interface is dedicated for registering new API endpoints.
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


interface Interface_Hook_Register_REST_API_Endpoints{


   /**
    * Initiates the hooks.
    *
    * @return void
    */
   public static function init();



   /**
    * Registers endpoints.
    *
    * @return void
    */
   public static function register_endpoints();

}