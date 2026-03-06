<?php
/**
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


?>
<tr class="<?php echo PREFIX;?>-style">
   <td class="pt-0 pl-0 pr-0">
      <h3><?php _e('IP list', 'convesiopay-woocommerce');?></h3>
      <p class="description mb-10"><?php _e('In case your shop has some restrictions for inbound requests please whitelist our IPs:', 'convesiopay-woocommerce');?></p>
      <div><?php echo implode(', ', $ip_whitelist);?></div>
   </td>
</tr>

<?php foreach($tools as $tool):
   $btn_class = Util::array($tool)->get('btn_class');
   $btn_label = Util::array($tool)->get('btn_label', __('Click to run', 'convesiopay-woocommerce'));
   $hidden    = Util::array($tool)->get('hidden');

   if(empty($tool['id']) || empty($tool['name']) || empty($tool['description']) || $hidden){
      continue;
   }
   ?>
   <tr class="<?php echo PREFIX;?>-style">
      <td class="pl-0 pr-0">
         <h3><?php echo $tool['name'];?></h3>
         <div class="mb-10">
            <p class="description"><?php echo $tool['description'];?></p>
         </div>
         <?php if( ! empty($tool['warning']) ):?>
            <div class="mb-10 alertbox alertbox--yellow"><b><?php _e('Warning:', 'convesiopay-woocommerce');?></b> <?php echo $tool['warning'];?></div>
         <?php endif;?>
         <div>
            <button type="button" class="button <?php echo $btn_class;?>" data-<?php echo PREFIX;?>-run-tool="<?php echo $tool['id'];?>"><?php echo $btn_label;?></button>
         </div>
   </td>
</tr>
<?php endforeach;?>

<!-- Payment Gateway Migration Tool Card -->
<tr class="<?php echo PREFIX;?>-style">
  <td class="pl-0 pr-0">
    <h3><?php _e('Migrate Payment Gateway Subscriptions', 'convesiopay-woocommerce'); ?></h3>
    <div class="mb-10">
      <p><?php _e('Migrate your existing payment gateway subscriptions to ConvesioPay in one click. Currently we support migrating from Stripe and Authorize.net (SkyVerge) to ConvesioPay without customer interaction.', 'convesiopay-woocommerce'); ?></p>
    </div>
    <div style="background: #fff3cd; border-left: 6px solid #ffe066; padding: 16px; margin-bottom: 18px; color: #856404;">
      <strong>Important:</strong> Before using this tool, please contact <a href="mailto:pay@convesio.com">ConvesioPay Support</a> to arrange a Token Migration of your customers' cards. Do not proceed until you have confirmation from support.
    </div>
    <div>
                      <a href="<?php echo admin_url('admin.php?page=cpay-gateway-migration'); ?>" class="button"><?php _e('Open Migration Tool', 'convesiopay-woocommerce'); ?></a>
    </div>
  </td>
</tr>