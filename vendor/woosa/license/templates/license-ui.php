<?php
/**
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;

/**
 * @var array $values;
 */
?>

<tr class="<?php echo PREFIX;?>-style">
   <td class="p-0">
      <div class="w-800">
         <div class="tb"><?php echo esc_html( $values['title'] ); ?></div>
         <div class="mt-20"><?php echo $values['desc']; ?></div>

         <div class="mt-20">
            <span class="tb"><?php _e('Status ', 'convesiopay-woocommerce');?></span>
            <?php echo $status; ?>
         </div>
         <div>
            <span class="tb"><?php _e('Activations ', 'convesiopay-woocommerce');?></span>
            <?php echo $activaion_stats;?>
         </div>
         <div class="mt-10 license-section">
            <input type="text" class="license-key" id="<?php echo $values['id'];?>" value="<?php echo $license->key;?>" placeholder="<?php _e('License Key', 'convesiopay-woocommerce');?>" autocomplete="off">
            <?php if($license->is_active()):?>
               <button type="button" class="button button-secondary" data-<?php echo PREFIX;?>-license="deactivate"><?php _e('Deactivate', 'convesiopay-woocommerce');?></button>
               <button type="button" class="button button-link button-link" data-<?php echo PREFIX;?>-license="get_update"><?php _e('Check for update', 'convesiopay-woocommerce');?></button>
            <?php else:?>
               <button type="button" class="button button-primary" data-<?php echo PREFIX;?>-license="activate"><?php _e('Activate', 'convesiopay-woocommerce');?></button>
            <?php endif;?>
         </div>
      </div>
   </td>
</tr>