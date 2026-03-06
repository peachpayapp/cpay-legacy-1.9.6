<?php
/**
 * My Account Hook
 *
 * @author ConvesioPay - based on the Woosa WC integration
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class My_Account_Hook implements Interface_Hook{


   /**
    * Initiates the hooks.
    *
    * @return void
    */
   public static function init(){

      add_action(PREFIX . '\core\state\activated', [__CLASS__, 'flush_rewrite_rules']);

      add_action('init', [__CLASS__, 'page_endpoint']);

      add_filter('woocommerce_account_menu_items', [__CLASS__, 'page_menu_item'], 10, 1);
      add_action('woocommerce_account_stored-cards_endpoint', [__CLASS__, 'stored_cards_content']);
   }



   /**
    * Removes rewrite rules and then recreates rewrite rules.
    *
    * @return void
    */
   public static function flush_rewrite_rules(){

      self::page_endpoint();

      flush_rewrite_rules();
   }



   /**
    * Adds page endpoint.
    *
    * @since 1.0.3
    * @return void
    */
   public static function page_endpoint() {

      add_rewrite_endpoint( 'stored-cards', \EP_PAGES );

   }



   /**
    * Adds page menu item.
    *
    * @since 1.0.3
    * @param array $items
    * @return array
    */
   public static function page_menu_item( $items ) {

      $new_items = [];

      foreach ($items as $key => $value) {
         $new_items[$key] = $value;
         if( 'edit-account' === $key){
            $new_items['stored-cards'] = __( 'Stored Cards', 'convesiopay-woocommerce' );
         }
      }

      return $new_items;

   }



   /**
    * Renders the content of stored cards page.
    *
    * @since 1.0.3
    * @return string
    */
   public static function stored_cards_content() {

      $cards = Service::checkout()->get_ec_stored_cards();

      ?>

      <h3><?php _e('Stored Creditcards', 'convesiopay-woocommerce');?></h3>

      <?php if( empty($cards) ):?>

         <p><?php _e('There are not stored cards yet.', 'convesiopay-woocommerce');?></p>

      <?php else:?>

         <div class="<?php echo PREFIX;?>-list-cards">

            <?php foreach($cards as $item):?>
               <div class="<?php echo PREFIX;?>-list-cards__item">
                  <div class="<?php echo PREFIX;?>-card-details">
                     <div class="<?php echo PREFIX;?>-card-details__logo"><img src="<?php echo esc_attr('https://checkoutshopper-test.adyen.com/checkoutshopper/images/logos/' . $item['brand']);?>.svg" title="<?php echo esc_attr($item['name']);?>" alt="<?php echo esc_attr($item['name']);?>"></div>
                     <div class="<?php echo PREFIX;?>-card-details__number">************<?php echo esc_html($item['lastFour']);?></div>
                     <div><?php printf(__('Expires: %s', 'convesiopay-woocommerce'), "{$item['expiryMonth']}/{$item['expiryYear']}");?></div>
                     <div><?php echo esc_html($item['holderName']);?></div>
                     <div class="<?php echo PREFIX;?>-card-details__remove" data-remove-sci="<?php echo esc_attr($item['id']);?>" title="<?php _e('Remove this card', 'convesiopay-woocommerce');?>"><span class="dashicons dashicons-no-alt"></span></div>
                  </div>
               </div>
            <?php endforeach;?>

         </div>

      <?php endif;?>

      <?php
   }


}