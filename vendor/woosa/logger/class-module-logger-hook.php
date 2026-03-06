<?php
/**
 * Module Logger Hook
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Module_Logger_Hook implements Interface_Hook{


   /**
    * Initiates the hooks.
    *
    * @return void
    */
   public static function init(){

      add_action('init', [__CLASS__, 'process_table_bulk_actions']);
      add_action('admin_init', [Module_Logger::class, 'delete_old_entries']);

      register_shutdown_function([__CLASS__, 'log_errors']);

   }



   /**
    * Process the bulk action on Module_Logger table
    *
    * @return void
    */
   public static function process_table_bulk_actions() {
      $current_action = null;

      if ( isset( $_REQUEST['action'] ) && -1 != $_REQUEST['action'] ) {

         $current_action = $_REQUEST['action'];

      }

      if (!empty($current_action)) {

         switch ($current_action) {

            case 'download':

               self::download_logs(Util::array($_POST)->get(PREFIX . '-log'));

               break;

            case 'delete':

               self::delete_logs(Util::array($_POST)->get(PREFIX . '-log'));

               break;

         }

      }

   }



   /**
    * Download logs
    *
    * @param $logs
    * @return void
    */
   public static function download_logs($logs) {

      if (!empty($logs)) {

         $log_files = [];

         foreach ($logs as $log) {

            if (file_exists($log)) {

               $log_files[] = $log;

            }
         }

         if (empty($log_files)) {
            return;
         }

         if (1 === count($log_files)) {

            $log_file = $log_files[0] ?? null;

            if (empty($log_file)) {
               return;
            }

            header("Content-Type: plain/text");
            header("Content-disposition: attachment; filename=\"" . basename($log_file) . "\"");
            header('Content-Length: ' . filesize($log_file));

            readfile($log_file);
            exit();

         } else {

            $archive_file_name = Module_Logger::get_file_dir() . PREFIX . '-logs.zip';
            if (file_exists($archive_file_name)) {// remove temp archive if already exists
               unlink($archive_file_name);
            }

            $zip = new \ZipArchive();

            if ($zip->open($archive_file_name, \ZIPARCHIVE::CREATE )!==TRUE) {
               exit(sprintf(
                  __(  "Cannot open <%s>\n", 'convesiopay-woocommerce'),
                  $archive_file_name
               ));
            }

            foreach($log_files as $log_file) {

               $zip->addFile($log_file, basename($log_file));

            }

            $zip->close();
            header("Content-type: application/zip");
            header("Content-Disposition: attachment; filename=\"". basename($archive_file_name) ."\"");
            header('Content-Length: ' . filesize($archive_file_name));
            header("Pragma: no-cache");
            header("Expires: 0");
            readfile($archive_file_name);
            // remove temp archive
            unlink($archive_file_name);
            exit();

         }

      }

   }



   /**
    * Delete the logs files
    *
    * @param $logs
    * @return void
    */
   public static function delete_logs($logs) {

      if (!empty($logs)) {

         foreach ($logs as $log_file) {

            if (file_exists($log_file)) {
               unlink($log_file);
            }

         }

      }

   }



   /**
    * Log fatal errors
    *
    * @return void
    */
   public static function log_errors() {

      $error = error_get_last();

      if ( $error && in_array( $error['type'], array( E_ERROR, E_PARSE, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR ), true ) ) {

         Module_Logger::critical(
            sprintf( __( '%1$s in %2$s on line %3$s', 'convesiopay-woocommerce' ), $error['message'], $error['file'], $error['line'] ) . PHP_EOL,
         );

      }
   }


}
