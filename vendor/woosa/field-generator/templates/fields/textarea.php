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
   <td class="forminp forminp-<?php echo esc_attr( sanitize_title( $field['type'] ) ); ?>">
      <textarea
         name="<?php echo $instance->get_field_name( $field['id'] ); ?>"
         id="<?php echo $instance->get_field_id($field['id']); ?>"
         style="<?php echo esc_attr( $field['css'] ); ?>"
         class="<?php echo esc_attr( $field['class'] ); ?>"
         placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>"
         data-required="<?php echo esc_attr( $field['required'] ); ?>"
         <?php echo $instance->get_field_custom_attributes($field['custom_attributes']); ?>
         ><?php echo esc_textarea( $field['value'] ); // WPCS: XSS ok. ?></textarea>

      <?php echo $instance->get_field_description($field); ?>
   </td>
</tr>