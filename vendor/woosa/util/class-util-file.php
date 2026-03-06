<?php
/**
 * Util File
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Util_File{


   /**
    * Checks the status of a given remote URL
    *
    * @param string $url
    * @return int
    */
   public static function check_remote_status( $url ) {

      $ch = curl_init( $url );

      curl_setopt( $ch, CURLOPT_NOBODY, true );
      curl_exec( $ch );
      $code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
      curl_close( $ch );

      return $code;

   }



   /**
    * Downloads a remote file locally.
    *
    * @param string $url - remote url
    * @param string $local_file - local file path
    * @return array status
    */
   public static function remote_download( $url, $local_file ) {

      $status = self::check_remote_status( $url );
      $response = [
         'status' => 'success',
      ];

      if ( $status >= 400 ) {

         $response = [
            'status' => 'error',
            'message' => __( 'The remote file cannot be accessed, please try again or contact us if this problem still persists. Status: ' . $status . '. File url: ' . $url, 'convesiopay-woocommerce' ),
         ];

      }else{

         //check if server allows opening remote URL
         if ( ini_get( 'allow_url_fopen' ) != 1 ) {

            $fileHandle = fopen( $local_file, "w" ); // Open the file on our server for writing.

            if ( false === $fileHandle ) {

               $response = [
                  'status' => 'error',
                  'message' => __( 'Unable to open file for writting at the followin location: ' . $local_file, 'convesiopay-woocommerce' ),
               ];

            }else{

               $handle     = curl_init();
               $max_time   = ini_get( "max_execution_time" ) - 1;

               curl_setopt_array(
                  $handle,
                  [
                     CURLOPT_URL       => $url,
                     CURLOPT_FILE      => $fileHandle,
                     CURLOPT_TIMEOUT   => $max_time,
                  ]
               );

               curl_exec( $handle );

               if ( curl_errno( $handle ) ) {

                  if ( stripos( curl_error( $handle ), 'Operation timed out' ) !== false  ) {

                     //remove the file
                     unlink( $local_file );

                     $response = [
                        'status' => 'error',
                        'message' => sprintf(
                           __( 'File download process failed because the process takes longer than the server allows, please increase the PHP <code>max_execution_time</code> then try again, %sclick here%s for more details.', 'convesiopay-woocommerce' ),
                           '<a href="https://support.woosa.nl/hc/en-us/articles/360006021138" target="_blank">',
                           '</a>'
                        ),
                     ];

                  }else{

                     Util::log()->error([
                        'message'  => curl_error( $handle ),
                        'url'    => $url
                     ], __FILE__, __LINE__ );

                     $response = [
                        'status' => 'error',
                        'message' => __( 'An error occured when download a remote file. For details check the log files.', 'convesiopay-woocommerce' ),
                     ];
                  }

               }

               curl_close( $handle );
               fclose( $fileHandle );

            }

         }else{

            if ( $fp_remote = fopen( $url, 'rb' ) ) {

               // read buffer, open in wb mode for writing
               if ( $fp_local = fopen( $local_file, 'wb' ) ) {

                  // read the file, buffer size 8k
                  while ($buffer = fread($fp_remote, 8192)) {
                     fwrite($fp_local, $buffer);
                  }

                  fclose($fp_local);

               }else{

                  Util::log()->error([
                     'message'  => error_get_last(),
                     'url'    => $url
                  ], __FILE__, __LINE__ );

                  $response = [
                     'status' => 'error',
                     'message' => __( 'An error occured when download a remote file. For details check the log files.', 'convesiopay-woocommerce' ),
                  ];
               }

               fclose($fp_remote);

            }else{

               Util::log()->error([
                  'message'  => error_get_last(),
                  'url'    => $url
               ], __FILE__, __LINE__ );

               $response = [
                  'status' => 'error',
                  'message' => __( 'An error occured when download a remote file. For details check the log files.', 'convesiopay-woocommerce' ),
               ];
            }

         }

      }

      return $response;

   }



   /**
    * Download image from url and create the wp media library attachment
    *
    * @param string $url The file url
    * @param int $post_id
    * @return int|\WP_Error
    */
   public static function download_image_from_url(string $url, $post_id = 0) {

      if ( ! function_exists( 'download_url' ) ) {
         require_once ABSPATH . 'wp-admin/includes/file.php';
      }

      if ( ! function_exists( 'wp_read_image_metadata' ) ) {
         require_once ABSPATH . 'wp-admin/includes/image.php';
      }

      if ( ! function_exists( 'media_handle_sideload' ) ) {
         require_once ABSPATH . 'wp-admin/includes/media.php';
      }

      $url = Util::strip_url($url);

      $tmp = download_url( $url );

      $file_array = array(
         'name' => basename( $url ),
         'tmp_name' => $tmp
      );

      if ( is_wp_error( $tmp ) ) {
         return $tmp;
      }

      $attachment_id = media_handle_sideload( $file_array, $post_id );

      if ( ! is_wp_error( $attachment_id ) ) {
         add_post_meta($attachment_id, Util::prefix('plugin_version'), VERSION, true);
      }

      return $attachment_id;
   }



   /**
    * Check if the attachment was downloaded from url
    *
    * @param $attachment_id
    * @return bool
    */
   public static function has_downloaded_attachment($attachment_id) {
      return metadata_exists('post', $attachment_id, Util::prefix('plugin_version'));
   }



   /**
    * Builds the path by the given fragments.
    *
    * @param array $fragments
    * @return string
    */
    public static function build_path($fragments){
      return is_array($fragments) ? join(DIRECTORY_SEPARATOR, $fragments) : str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fragments);
   }
}