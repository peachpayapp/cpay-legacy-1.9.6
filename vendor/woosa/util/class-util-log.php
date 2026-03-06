<?php
/**
 * Util Log
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Util_Log{


   /**
    * Sets the log.
    *
    * @param mixed $message
    * @param string $file
    * @param string $line
    * @param string $type
    * @return void
    */
   protected function set($message, $file = '', $line = '', $type = 'error'){

      if(method_exists(__NAMESPACE__ . '\\Module_Logger', 'log')){

         Module_Logger::log($type, $message, $file, $line);

      //backward compatibility - still use WC logs until we move to the new Logger
      }elseif(function_exists('wc_get_logger')){

         $message = !is_string($message) ? print_r( $message, true ) : $message;

         if(!empty($file) && !empty($line)){
            $message = "{$message} thrown in {$file}:{$line}";
         }

         $log = wc_get_logger();
         $log->log( $type, $message, array( 'source' => DIR_NAME ) );

      }else{

         $message = !is_string($message) ? print_r( $message, true ) : $message;

         if(!empty($file) && !empty($line)){
            $message = "{$message} thrown in {$file}:{$line}";
         }

         $time_string = gmdate('c', time());
         $type_string = strtoupper($type);

         error_log("{$time_string} {$type_string} {$message}" . PHP_EOL, 3, DEBUG_FILE);
      }
   }



   /**
    * Sets error log.
    *
    * @param mixed $message
    * @param string $file
    * @param string $line
    * @return void
    */
   public function error($message, $file = '', $line = ''){
      $this->set($message, $file, $line);
   }



   /**
    * Sets debug log.
    *
    * @param mixed $message
    * @param string $file
    * @param string $line
    * @return void
    */
   public function debug($message, $file = '', $line = ''){
      $this->set($message, $file, $line, 'debug');
   }



   /**
    * Sets warning log.
    *
    * @param mixed $message
    * @param string $file
    * @param string $line
    * @return void
    */
   public function warning($message, $file = '', $line = ''){
      $this->set($message, $file, $line, 'warning');
   }

}