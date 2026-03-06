<?php
/**
 * Interface API Client
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


interface Interface_API_Client{


   /**
    * The API version.
    *
    * @return string
    */
   public function version();



   /**
    * The base API url.
    *
    * @param string $endpoint
    * @param bool $use_service - whether or not the endpoint should contain the service
    * @return string
    */
   public function base_url(string $endpoint, bool $use_service = true);



   /**
    * The list of request headers.
    *
    * @param array $items
    * @return array
    */
   public function headers(array $items = []);



   /**
    * Checks whether or not the plugin is authorized/configured to send requests.
    *
    * @return boolean
    */
   public function is_authorized();



   /**
    * Checks whether or not the test mode is enabled.
    *
    * @return bool
    */
   public function is_test_mode();



   /**
    * Processes the authorization.
    *
    * @return object
    */
   public function authorize();



   /**
    * Revokes the authorization.
    *
    * @return void
    */
   public function revoke();



   /**
    * Retrieves the access token (or API key).
    *
    * @return string
    */
   public function get_access_token();



   /**
    * Checks whether or not the access token has expired.
    *
    * @return boolean
    */
   public function is_access_token_expired();



   /**
    * Sends the request.
    *
    * @param string $endpoint
    * @param array $payload
    * @param string $method
    * @param array $args - headers, timeout, etc
    * @return void
    */
   public function send_request(string $endpoint, array $payload = [], string $method = 'POST', array $args = []);



   /**
    * Sleeps the script to stay with the API rate limit.
    *
    * Use `usleep(700000); //sleep for 0.7 seconds`
    *
    * @return void
    */
   public static function delay_process_for_rate_limit();



   /**
    * Retrieves the error message based on the given code.
    *
    * @param string $code
    * @param array $args
    * @return string
    */
   public static function get_error_message(string $code, array $args = []);
}