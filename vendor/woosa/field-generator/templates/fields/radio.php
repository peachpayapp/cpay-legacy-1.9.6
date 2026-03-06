<?php
/**
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


?>
<tr valign="top" class="<?php echo $is_disabled;?>">
   <th scope="row" class="titledesc">
      <label for="<?php echo $instance->get_field_id($field['id']); ?>"><?php echo esc_html( $field['title'] ); ?> <?php echo $instance->get_field_tooltip($field['desc_tip']);?></label>
   </th>
   <td class="forminp forminp-<?php echo $instance->get_field_type($field['type']); ?>">
      <fieldset>
         <?php echo $instance->get_field_description($field); ?>
         <ul>
         <?php
         foreach ( $field['options'] as $key => $val ) {
            ?>
            <li>
               <label>
                  <input
                     name="<?php echo $instance->get_field_name($field['id']); ?>"
                     type="<?php echo $instance->get_field_type($field['type']); ?>"
                     value="<?php echo esc_attr( $key ); ?>"
                     style="<?php echo esc_attr( $field['css'] ); ?>"
                     class="<?php echo esc_attr( $field['class'] ); ?>"
                     <?php echo $instance->get_field_custom_attributes($field['custom_attributes']); ?>
                     <?php checked( $key, $field['value'] ); ?>
                     /> <?php echo esc_html( $val ); ?>
               </label>
            </li>
            <?php
         }
         ?>
         </ul>
      </fieldset>
   </td>
</tr>