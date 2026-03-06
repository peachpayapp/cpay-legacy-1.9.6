<?php
/**
 * Interface DB Table
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


interface Interface_DB_Table{


   /**
    * Retrieves the database table name.
    *
    * @return string
    */
   public static function get_table_name();



   /**
    * Creates the database table if not exists.
    *
    * @return void
    */
   public static function create_table();



   /**
    * Deletes the database table.
    *
    * @return void
    */
   public static function delete_table();



   /**
    * Retrieves multiple table entries.
    *
    * @param array $args
    * @return array
    */
   public static function get_entries(array $args);



   /**
    * Retrieves a single table entry.
    *
    * @param int $id
    * @return array|null
    */
   public static function get_entry($id);



   /**
    * Creates multiple new table entries.
    *
    * @param array $args
    * @return void
    */
   public static function create_entries(array $args);



   /**
    * Creates a single table entry.
    *
    * @param array $args
    * @return void
    */
   public static function create_entry(array $args);



   /**
    * Updates multiple table entries.
    *
    * @param array $args
    * @return void
    */
   public static function update_entries(array $args);



   /**
    * Updates a single table entry.
    *
    * @param int $id
    * @param array $args
    * @return void
    */
   public static function update_entry($id, array $args);



   /**
    * Deletes multiple table entries.
    *
    * @param array $args
    * @return void
    */
   public static function delete_entries(array $args);



   /**
    * Deletes a single table entry.
    *
    * @param int $id
    * @return void
    */
   public static function delete_entry($id);


}