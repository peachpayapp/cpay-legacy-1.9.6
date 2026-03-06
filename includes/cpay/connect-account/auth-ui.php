<?php
/**
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;

/**
 * @var Module_Authorization $authorization
 */

   // Set auth vars
   $test_mode = 'yes' == get_option( PREFIX . '_testmode' );
   if ( $test_mode ) {
      $is_authorized = 'yes' == get_option( PREFIX . '_is_authorized_test' );
   } else {
      $is_authorized = 'yes' == get_option( PREFIX . '_is_authorized_live' );
   }
   $cpay_connect_acct_url = admin_url('admin-ajax.php');
   $cpay_requesting_store_url = site_url();
   $cpay_requesting_store_webhook = rest_url() . 'wc-convesiopay/payment-status';

?>

<tr class="<?php echo PREFIX;?>-style">
   <td class="p-0">
      <div>
         <span class="tb"><?php _e('Status ', 'convesiopay-woocommerce');?></span>
         <?php echo $status; ?>
      </div>

      <?php if ( $is_authorized ): ?>
         <p class="pt-20">If you are no longer interested in using ConvesioPay for WooCommerce, you can disconnect your account and unauthorize this store from making transactions.</p>
         <div class="pt-15">
            <a id="cpay-connect-account-btn" class="button button-primary thickbox" title="Disconnect Your Account" href="<?php echo $cpay_connect_acct_url . '?action=cpay_disconnect_modal'; ?>" data-cpay-api-request-url="<?php echo $cpay_connect_acct_url; ?>">Disconnect your account</a>
         </div>
      <?php else: ?>
         <p class="pt-20">
            In order to get started with ConvesioPay for WooCommerce, we need to connect your account and authorize this store to make transactions.</p>
         <div class="pt-15">
            <a id="cpay-connect-account-btn" class="button button-primary thickbox" title="Connect Your Account" href="<?php echo $cpay_connect_acct_url . '?action=cpay_connect_modal'; ?>" data-cpay-api-request-url="<?php echo $cpay_connect_acct_url; ?>" data-cpay-requesting-store-url="<?php echo $cpay_requesting_store_url; ?>" data-cpay-requesting-store-webhook="<?php echo $cpay_requesting_store_webhook; ?>">Connect your account</a>
         </div>
      <?php endif; ?>
   </td>
</tr>