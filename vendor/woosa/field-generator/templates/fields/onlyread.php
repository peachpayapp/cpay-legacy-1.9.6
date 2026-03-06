<?php
/**
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


?>
<tr valign="top">
   <th>
      <label for="<?php echo $instance->get_field_id($field['id']); ?>"><?php echo esc_html( $field['title'] ); ?> <?php echo $instance->get_field_tooltip($field['desc_tip']);?></label>
   </th>
   <td class="forminp">
      <span>
         <?php if(empty($field['value'])){
            echo '<em>'.__('No value available', 'convesiopay-woocommerce').'</em>';
         }else{
            echo $field['value'];
         }
         ?>
      </span>
   </td>
</tr>