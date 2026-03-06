<?php
/**
 * Service Hook
 *
 * @author ConvesioPay - based on the Woosa WC integration
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Service_Hook implements Interface_Hook
{


   /**
    * @var Service
    */
   private static Service $service;



   /**
    * Init hook
    *
    * @return void
    */
   public static function init() {

      add_action(PREFIX . '\request\sent', [__CLASS__, 'handle_request'], 10, 2);

      self::set_up();

   }



   /**
    * Set up the service
    *
    * @return void
    */
   public static function set_up() {

      self::$service = new Service();

   }


   /**
    * Checks request response and disables the proxy in case of failure.
    *
    * @param object $result
    * @param Request $request
    * @return void
    */
   public static function handle_request($result, $request) {

      if (
         in_array($result->status, [500, 503, 504])
         && (
            str_contains($request->get_url(), self::$service->domain_proxy_1())
            || str_contains($request->get_url(), self::$service->domain_proxy_2())
         )
      ) {

         Transient::set(Service::PROXY_AVAILABILITY_TRANSIENT_NAME, 'no', HOUR_IN_SECONDS);

      }

   }

}
