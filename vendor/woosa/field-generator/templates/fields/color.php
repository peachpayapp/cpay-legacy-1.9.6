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
   <td class="forminp forminp-<?php echo esc_attr( sanitize_title( $field['type'] ) ); ?>">&lrm;
      <span class="colorpickpreview" style="background: <?php echo esc_attr( $field['value'] ); ?>">&nbsp;</span>
      <input
         name="<?php echo $instance->get_field_name( $field['id'] ); ?>"
         id="<?php echo $instance->get_field_id($field['id']); ?>"
         type="text"
         dir="ltr"
         style="<?php echo esc_attr( $field['css'] ); ?>"
         value="<?php echo esc_attr( $field['value'] ); ?>"
         class="<?php echo esc_attr( $field['class'] ); ?>colorpick"
         placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>"
         data-required="<?php echo esc_attr( $field['required'] ); ?>"
         <?php echo $instance->get_field_custom_attributes($field['custom_attributes']); ?>
         />
         <?php echo $instance->get_field_description($field); ?>
         <div id="colorPickerDiv_<?php echo esc_attr( sanitize_title( $field['id'] ) ); ?>" class="colorpickdiv" style="z-index: 100;background:#eee;border:1px solid #ccc;position:absolute;display:none;"></div>
   </td>
</tr>