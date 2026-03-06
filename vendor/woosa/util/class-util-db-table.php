<?php
/**
 * Util DB Table
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Util_DB_Table{



   /**
    * Get the WPDB
    *
    * @return \wpdb
    */
   public static function db() {
      global $wpdb;

      return $wpdb;
   }



   /**
    * Create the DB table
    *
    * @param string $table_name
    * @param string $columns
    *    id mediumint(9) NOT NULL AUTO_INCREMENT,
    *    time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
    *    name tinytext NOT NULL,
    *    text text NOT NULL,
    *    url varchar(55) DEFAULT '' NOT NULL,
    *    PRIMARY KEY  (id)
    * @param string $charset_collate
    * @return void
    */
    public static function create(string $table_name, string $columns, string $charset_collate = '') {

      if (empty($charset_collate)) {
         $charset_collate = self::db()->get_charset_collate();
      }

      $sql = "CREATE TABLE $table_name (
         $columns
      ) $charset_collate;";

      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

      maybe_create_table($table_name, $sql);
   }



   /**
    * Delete the DB table
    *
    * @param string $table_name
    * @return void
    */
   public static function delete(string $table_name) {
      self::db()->query( "DROP TABLE IF EXISTS {$table_name}" );
   }



   /**
    * Check if table is created
    *
    * @param string $table_name
    * @return bool
    */
   public static function is_created(string $table_name) {
      return self::db()->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
   }

}
