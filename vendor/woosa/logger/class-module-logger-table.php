<?php
/**
 * Module Logger Table
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;

use WP_List_Table;

//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


if( ! class_exists( 'WP_List_Table' ) ) {
   require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Module_Logger_Table extends WP_List_Table {


   public function __construct(){

	   //Set parent defaults
	   parent::__construct( array(
         'singular'  => PREFIX . '-log',     //singular name of the listed records
	      'plural'    => PREFIX . '-logs',    //plural name of the listed records
	      'ajax'      => false        //does this table support ajax?
      ) );

   }



   /**
    * Retrieves the list of bulk actions available for this table.
    *
    * @return array
    */
   protected function get_bulk_actions() {

      $actions = array(
         'download' => __( 'Download' , 'convesiopay-woocommerce'),
         'delete' => __( 'Delete' , 'convesiopay-woocommerce'),
      );

      return $actions;

   }



   /**
    * The checkbox callback
    *
    * @param $item
    * @return string|void
    */
   function column_cb($item){

      return sprintf(
         '<input type="checkbox" name="%1$s[]" value="%2$s" />',
         /*$1%s*/ $this->_args['singular'],
         /*$2%s*/ $item['file']
      );

   }



   /**
    * Prepare the items for the table to process
    *
    * @return Void
    */
   public function prepare_items() {

      $columns = $this->get_columns();
      $hidden = $this->get_hidden_columns();
      $sortable = $this->get_sortable_columns();

      $data = $this->table_data();
      $data = $this->search($data);

      usort( $data, array( &$this, 'sort_data' ) );

      $perPage = 10;
      $currentPage = $this->get_pagenum();
      $totalItems = count($data);

      $this->set_pagination_args( array(
         'total_items' => $totalItems,
         'per_page'    => $perPage
      ) );

      $data = array_slice($data,(($currentPage-1)*$perPage),$perPage);

      $this->_column_headers = array($columns, $hidden, $sortable);
      $this->items = $data;

   }



   /**
    * Override the parent columns method. Defines the columns to use in your listing table
    *
    * @return array
    */
   public function get_columns() {

      $columns = array(
         'cb' => '<input type="checkbox">',
         'source' => __('Source', 'convesiopay-woocommerce'),
         'file_date' => __('Date Created', 'convesiopay-woocommerce'),
         'file_size' => __('File Size', 'convesiopay-woocommerce'),
      );

      return $columns;

   }



   /**
    * Define which columns are hidden
    *
    * @return array
    */
   public function get_hidden_columns() {
      return [];
   }



   /**
    * Define the sortable columns
    *
    * @return array
    */
   public function get_sortable_columns() {

      return array(
         'source' => array('source', false),
         'file_date' => array('file_date', false),
         'file_size' => array('file_bytes', false),
      );

   }



   /**
    * Get the logs data
    *
    * @return array
    */
   public function table_data() {
      return Module_Logger::get_entries();
   }



   /**
    * Search the items
    *
    * @param array $data
    * @return array
    */
   public function search($data) {
      $search_query = $this->get_search();

      if (!empty($search_query)) {

         $search_results = [];

         foreach ($data as $item) {

            if (preg_match('/'.$search_query.'/i', Util::array($item)->get('file_name'))) {
               $search_results[] = $item;
            }

         }

         return $search_results;

      }

      return $data;
   }



   /**
    * Get the current file search
    *
    * @return string
    */
   public function get_search() {

      if ( ! empty( $_REQUEST['_wp_http_referer'] ) && isset($_REQUEST['s']) ) {
         if (empty($_REQUEST['s'])) {
            wp_redirect(remove_query_arg( array( '_wp_http_referer', '_wpnonce', 's', 'paged' ), wp_unslash( $_SERVER['REQUEST_URI'] ) ));
         } else {
            wp_redirect(
               add_query_arg(
                  [
                     's' => $_REQUEST['s'],
                  ],
                  remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), wp_unslash( $_SERVER['REQUEST_URI'] ) )
               )
            );
         }
         exit;
      }

      $search = '';

      if (isset($_REQUEST['s'])) {
         $search = $_REQUEST['s'];
      }

      return $search;

   }



   /**
    * Define what data to show on each column of the table
    *
    * @param  array $item        Data
    * @param  string $column_name - Current column name
    *
    * @return Mixed
    */
   public function column_default( $item, $column_name ) {

      switch( $column_name ) {
         case 'source':
            return sprintf(
               '<a href="%s">%s</a>',
               add_query_arg(
                  [
                     'log_file' => base64_encode($item['file_name']),
                  ],
                  Module_Settings::get_tab_url(['slug' => Module_Logger_Hook_Settings::id()])
               ),
               $item[ $column_name ]
            );
         case 'file_date':
            return date( 'Y-m-d', $item[ $column_name ]);
         case 'file_size':
            return $item[ $column_name ];

         default:
            return print_r( $item, true ) ;
      }

   }



   /**
    * Allows you to sort the data by the variables set in the $_GET
    *
    * @return Mixed
    */
   private function sort_data( $a, $b ) {

      $orderby = 'file_date';
      $order = 'desc';

      if(!empty($_GET['orderby'])) {
         $orderby = $_GET['orderby'];
      }

      if(!empty($_GET['order'])) {
         $order = $_GET['order'];
      }

      if($order === 'asc')  {
         return ($a[$orderby] < $b[$orderby]) ? -1 : 1;
      }

      return ($a[$orderby] > $b[$orderby]) ? -1 : 1;

   }

}
