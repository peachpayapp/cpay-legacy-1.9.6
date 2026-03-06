<?php
/**
 * Module Logger
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;

use DateInterval;
use DateTime;

//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Module_Logger{


   /**
    * Gets the maximum file size
    *
    * @return float
    */
   public static function get_max_file_size() {
      return (float) apply_filters(PREFIX . '\logger\get_max_file_size', 600 * 1024);
   }



   /**
    * Sets the log.
    *
    * @param string $type
    * @param string|array $message
    * @param string $path
    * @param string $line
    * @return void
    */
   public static function log($type, $message, $path = '', $line = '', $context = ''){

      $timestamp = time();

      if (!empty($message)) {

         $file_path  = self::get_file_path($context);
         $directory = dirname($file_path);

         if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
         }

         if (!file_exists($file_path)) {
            touch($file_path);
         }

         $logs_entry = self::format_entry($timestamp, $type, $message, $path, $line);

         file_put_contents($file_path, $logs_entry . PHP_EOL, FILE_APPEND);
      }
   }



   /**
    * Sets error log.
    *
    * @param string|array $message
    * @param string $path
    * @param string $line
    * @return void
    */
   public static function error($message, $path = '', $line = ''){
      self::log('error', $message, $path, $line, 'error');
   }



   /**
    * Sets warning log.
    *
    * @param string|array $message
    * @param string $path
    * @param string $line
    * @return void
    */
   public static function warning($message, $path = '', $line = ''){
      self::log('warning', $message, $path, $line, 'warning');
   }



   /**
    * Sets debug log.
    *
    * @param string|array $message
    * @param string $path
    * @param string $line
    * @return void
    */
   public static function debug($message, $path = '', $line = ''){
      self::log('debug', $message, $path, $line, 'debug');
   }



   /**
    * Log fatal errors
    *
    * @param $message
    * @return void
    */
   public static function critical($message) {
      self::log('critical', $message, '', '', 'fatal-errors');
   }



   /**
    * Get the logs dir
    *
    * @return string
    */
   public static function get_file_dir() {
      return trailingslashit( Util::array(wp_upload_dir())->get('basedir') ) . 'woosa-logs/';
   }



   /**
    * Get the log file path. that is less than max file size
    *
    * @return string
    */
   public static function get_file_path($context = '') {

      $date = date( 'Y-m-d', time() );

      if (!empty($context)) {
         $context .= '-';
      } else {
         $context = '';
      }

      $name = defined(__NAMESPACE__ . '\DIR_NAME') ? DIR_NAME . '-' : '';

      //make generic file with no name
      if('fatal-errors-' === $context){
         $name = '';
      }

      $log_file_path = self::get_file_dir() . $context . $name . $date . '.log';

      $log_number = 1;

      while(file_exists($log_file_path)) {

         // file exists but it does not reach the limit
         if (filesize( $log_file_path ) < self::get_max_file_size()) {
            break;
         }

         $log_number++;

         // use another name if file not exists and not reaches limit
         $log_file_path = self::get_file_dir() . $context . $name . $date . '-' . $log_number . '.log';
      }

      return $log_file_path;
   }



   /**
    * Format the log entry
    *
    * @param int $timestamp
    * @param string $type
    * @param string|array $message
    * @param string $file
    * @param string $line
    * @return string
    */
   public static function format_entry($timestamp, $type, $message, $file = '', $line = '') {

      $message = !is_string($message) ? print_r( $message, true ) : $message;

      if(!empty($file) && !empty($line)){
         $message = "{$message} thrown in {$file}:{$line}";
      }

      $time_string = gmdate('c', $timestamp);
      $type_string = strtoupper($type);
      return "{$time_string} {$type_string} {$message}" . PHP_EOL;

   }



   /**
    * Gets the log list.
    *
    * @return array
    */
   public static function get_entries() {

      $results = [];
      $files   = glob(self::get_file_dir() . "*.log");

      foreach ($files as $file) {
         $results[] = [
            'file'       => $file,
            'source'     => self::get_source($file),
            'file_name'  => basename($file),
            'file_size'  => self::filesize_formatted($file),
            'file_bytes' => filesize($file),
            'file_date'  => filemtime($file),
         ];
      }

      return $results;
   }



   /**
    * Get the filesize formatted for file
    *
    * @param $path
    * @return string
    */
   public static function filesize_formatted($path) {

      $size = filesize($path);
      $units = array( 'B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
      $power = $size > 0 ? floor(log($size, 1024)) : 0;

      return number_format($size / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
   }



   /**
    * Get the source from the file
    *
    * @param $file_path
    * @return string
    */
   public static function get_source($file_path) {

      if (!file_exists($file_path)) {
         return 'N/A';
      }

      $filename = basename($file_path);

      $levels = [
         'debug-',
         'warning-',
         'error-',
      ];

      $source = str_replace($levels, '', $filename);
      $source = preg_replace_callback('/^(.+)-(\d{4}-\d{2}-\d{2})(?:-(\d+))?\.log$/', function($matches) {
         return $matches[1] . (isset($matches[3]) ? ' (' . $matches[3] . ')' : '');
      }, $filename);

      return $source;
   }



   /**
    * Remove old logs once a day
    *
    * @return void
    */
   public static function delete_old_entries() {

      $last_check = Transient::get('logger:old_logs:last_check');

      if (empty($last_check)) {

         $now = new DateTime();
         $thirty_days_ago = $now->sub(DateInterval::createFromDateString('30 days'));

         $files = glob(self::get_file_dir() . "*.log");

         foreach ($files as $file) {

            if ($thirty_days_ago->getTimestamp() > filemtime($file)) {

               unlink($file);

            }

         }

         Transient::set('logger:old_logs:last_check', time(), \DAY_IN_SECONDS);

      }

   }


}
