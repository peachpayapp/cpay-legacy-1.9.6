<?php
/**
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


$field_name = $meta->is_product_type('variation') ? Util::prefix('fields[variable][variations]['.$meta->object_id.'][category]') : Util::prefix('fields[simple][category]');
?>
<div class="<?php echo PREFIX;?>-cs-box" data-<?php echo PREFIX;?>-cs-box="<?php echo $mcs->source;?>" data-<?php echo PREFIX;?>-cs-level="<?php echo $mcs->level;?>">
   <?php if( $meta->is_empty('category') ):?>
      <button type="button" class="button" data-<?php echo PREFIX;?>-cs-load-items="0"><?php _e('Please select', 'convesiopay-woocommerce');?></button>
   <?php endif;?>
   <div class="cs-trail" data-<?php echo PREFIX;?>-cs-trail>
      <?php
      if( ! $meta->is_empty('category') ){
         echo $mcs->get_trail_template($meta->get('category'));
      }
      ?>
   </div>
   <div class="cs-list" data-<?php echo PREFIX;?>-cs-list></div>
   <input type="hidden" name="<?php echo $field_name;?>" data-<?php echo PREFIX;?>-cs-input value="<?php echo $meta->get('category');?>"/>
</div>