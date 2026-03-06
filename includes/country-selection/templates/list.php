<?php
/**
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


?>

<?php if( ! empty($items) ):
   $cs_load_item = 'tree' === $level ? 'cs-load-item' : '';
   ?>
   <ul>
   <?php foreach($items as $item):?>
      <li>
         <button type="button" class="button widefat <?php echo $cs_load_item;?>" data-<?php echo PREFIX;?>-load-country-items="<?php echo $item['id'];?>"><?php echo $item['name'];?></button>
         <?php if('tree' === $level):?>
            <button type="button" class="button button-primary cs-select-item" data-<?php echo PREFIX;?>-load-country-items="<?php echo $item['id'];?>"><?php _e('Select', 'convesiopay-woocommerce');?></button>
         <?php endif;?>
      </li>
   <?php endforeach;?>
   </ul>
<?php endif;?>