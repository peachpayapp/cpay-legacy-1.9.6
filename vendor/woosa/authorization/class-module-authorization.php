<?php
/**
 * Module Authorization
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Module_Authorization{


   /**
    * The environment (live, test, etc).
    * Based on this the authorization status and the actions connect/disconnect will be processed separately.
    *
    * @var string|null
    */
   protected $environment = null;


   /**
    * Whether or not is marked as authorized.
    *
    * @var bool|null
    */
   protected $is_authorized = null;



   /**
    * Sets the current environment.
    *
    * @param string $value
    * @return void
    */
   public function set_env(string $value){
      $this->environment = $value;
   }



   /**
    * Sets the authorized flag.
    *
    * @return void
    */
   public function set_as_authorized(){
      Option::set( $this->env_flag(), 'yes' );
   }



   /**
    * Remove authorized flag.
    *
    * @return void
    */
   public function set_as_unauthorized(){
      Option::delete( $this->env_flag() );
   }



   /**
    * Retrieves the current environment.
    *
    * @return string
    */
   public function get_env(){

      if(is_null($this->environment)){

         $test_mode = Util::string_to_bool( Option::get('test_mode', Option::get('testmode', 'no')) );

         if( $test_mode ) {
            $this->environment = 'test';
         }else{
            $this->environment = 'live';
         }

      }

      return $this->environment;
   }



   /**
    * Gets the access status.
    *
    * @return string
    */
   public function get_status(){

      $value = __('Unauthorized', 'convesiopay-woocommerce');

      if( $this->is_authorized() ){
         $value = __('Authorized', 'convesiopay-woocommerce');
      }

      return apply_filters(PREFIX . '\authorization\get_status', $value, $this);
   }



   /**
    * The service which will be integrated (amazon, loyverse, etc).
    *
    * @return string
    */
   public function get_service(){
      return SETTINGS_TAB_ID;
   }



   /**
    * Checks whether or not it's marked as authorized.
    *
    * @var bool
    */
   public function is_authorized(){

      if(is_null($this->is_authorized)){
         $this->is_authorized = Util::string_to_bool( Option::get( $this->env_flag() ) );
      }

      return apply_filters(PREFIX . '\authorization\is_authorized', $this->is_authorized, $this);
   }



   /**
    * Grant access
    *
    * @return object
    */
   public function connect(){
      return $this->do_connection('connect');
   }



   /**
    * Revoke access.
    *
    * @return object
    */
   public function disconnect(){
      return $this->do_connection('disconnect');
   }



   /**
    * Runs connect or disconnect actions. Here other modules can hook up and run their logic.
    *
    * @param string $action
    * @return array - ['success' => true|false, 'message' => 'my message']
    */
   protected function do_connection(string $action){

      if( ! in_array($action, ['connect', 'disconnect']) ){
         return [
            'success' => false,
            'message' => 'Invalid action supplied!'
         ];
      }

      $output = ['success' => true];

      if( 'connect' === $action ) {

         $output = apply_filters(PREFIX . '\authorization\connect', $output, $this);

         if($output['success']){

            $this->set_as_authorized();

            do_action(PREFIX . '\authorization\access_granted', $this->environment);
         }

      }else{

         $output = apply_filters(PREFIX . '\authorization\disconnect', $output, $this);

         if($output['success']){

            $this->set_as_unauthorized();

            do_action(PREFIX . '\authorization\access_revoked', $this->environment);
         }
      }

      return $output;
   }



   /**
    * The authorized flag based on the environment.
    *
    * @return string
    */
   protected function env_flag(){
      return empty($this->get_env()) ? 'is_authorized' : "is_authorized_{$this->get_env()}";
   }



   /**
    * Displays the status and action button.
    *
    * @param string $environment
    * @return string
    */
   public static function render_status($environment = ''){

      $ma = new self();

      if( ! empty($environment) ){
         $ma->set_env($environment);
      }

      $data = json_encode([
         'action' => $ma->is_authorized() ? 'revoke' : 'authorize'
      ]);

      $color = $ma->is_authorized() ? 'green' : '#cc0000';
      $status = '<b>'.__('Status:', 'convesiopay-woocommerce').'</b> <span style="color: '.$color.';">'.$ma->get_status().'</span>';

      $btn_attr = "data-" . PREFIX . "-authorization='{$data}'";
      $btn_label = $ma->is_authorized() ? __( 'click to revoke', 'convesiopay-woocommerce' ) : __( 'click to authorize', 'convesiopay-woocommerce' );
      $btn = ' <button type="button" class="button button-link" '.$btn_attr.'>('.$btn_label.')</button>';

      $html = $status.$btn;

      return apply_filters(PREFIX . '\authorization\render_status', $html, $ma);
   }



   /**
    * Displays the section content.
    *
    * @param array $values
    * @param string $environment
    * @return string
    */
   public static function render($values = [], $environment = ''){

      $ma = new self();

      if( ! empty($environment) ){
         $ma->set_env($environment);
      }

      $data = json_encode([
         'action' => $ma->is_authorized() ? 'revoke' : 'authorize'
      ]);

      if ( $ma->is_authorized() ) {

         $svg = '<span class="icon-small">' .
            file_get_contents(untrailingslashit(plugin_dir_path(__FILE__)) . '/assets/images/icons/circle-check.svg')
            . '</span>';
         $status = '<span style="color: #68BD6D;fill: #68BD6D;">' . $svg . ' ' . __('Authorized', 'convesiopay-woocommerce').'</span>';

      } else {

         $svg = '<span class="icon-small">' .
            file_get_contents(untrailingslashit(plugin_dir_path(__FILE__)) . '/assets/images/icons/circle-xmark.svg')
            . '</span>';
         $status = '<span style="color: #B00;fill: #B00;">' . $svg . ' ' . __('Unauthorized', 'convesiopay-woocommerce').'</span>';

      }

      $btn_attr  = "data-" . PREFIX . "-authorization='{$data}'";
      $btn_label = $ma->is_authorized() ? __( 'Click to revoke', 'convesiopay-woocommerce' ) : __( 'Click to authorize', 'convesiopay-woocommerce' );

      echo Util::get_template('auth-ui.php', [
         'authorization' => $ma,
         'status' => $status,
         'button' => [
            'label' => $btn_label,
            'data-attr' => $btn_attr,
         ]
      ], DIR_PATH, '/includes/cpay/connect-account');
   }



   /**
    * Get the url for wiki page for current service
    *
    * @return string
    */
   public function get_wiki_article_url() {

      $wiki_article_url = '';

      switch ($this->get_service()) {

         case 'vidaxl':
            $wiki_article_url = 'https://help.woosa.com/en/articles/75686-how-to-authorise-vidaxl-in-your-plugin';
            break;

         case 'bigbuy':
            $wiki_article_url = 'https://help.woosa.com/en/articles/70028-how-do-i-authorize-the-plugin-with-my-bigbuy-account';
            break;

         case 'vandermeer':
            $wiki_article_url = 'https://help.woosa.com/en/articles/96720-how-do-i-authorize-the-plugin-with-my-van-der-meer-account';
            break;

         case 'kaufland':
            $wiki_article_url = 'https://help.woosa.com/en/articles/75677-how-to-authorize-kaufland-in-the-plugin-settings';
            break;

         case 'loyverse':
            $wiki_article_url = 'https://help.woosa.com/en/articles/57848-how-to-authorize-the-loyverse-plugin';
            break;

         case 'adyen':
            $wiki_article_url = 'https://help.woosa.com/en/articles/70024-how-to-authorize-the-adyen-plugin';
            break;

         default:
            $wiki_article_url = 'https://help.woosa.com/en/';

      }

      return $wiki_article_url;

   }


}