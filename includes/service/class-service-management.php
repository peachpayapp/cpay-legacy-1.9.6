<?php
/**
 * Service Management
 *
 * @author ConvesioPay - based on the Woosa WC integration
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Service_Management extends Service{


   /**
    * Version of the API.
    *
    * @var string
    */
   protected $version = 'v3';



   /**
    * Base of the URL.
    *
    * @param string $endpoint
    * @return string
    */
   public function base_url($endpoint = ''){

      if($this->test_mode){
         return 'https://management-test.'.$this->domain_proxy_1().'/' . $this->version . '/' . ltrim($endpoint, '/');
      }

      return 'https://management.'.$this->domain_proxy_1().'/' . $this->version . '/' . ltrim($endpoint, '/');

   }



   /**
    * Get all stores
    *
    * @param array $stores
    * @param int $page
    * @return array
    */
   public function get_stores($stores = [], $page = 1) {

      $response = Request::GET([
         'headers' => $this->headers(),
      ])->send(add_query_arg(
         [
            'pageSize' => 100,
            'pageNumber' => $page
         ],
         $this->base_url("/stores")
      ));

      if( $response->status == 200 ){


         if (!empty($response->body->data)) {

            $stores = array_merge($stores, (array)$response->body->data);

            if ($page < $response->body->pagesTotal) {

               $page++;

               $stores = $this->get_stores($stores, $page);

            }

         }


      }

      return $stores;
   }


}
