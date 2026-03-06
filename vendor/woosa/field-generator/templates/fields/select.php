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
      <select
         name="<?php echo $instance->get_field_name($field['id']); ?><?php echo ( 'multiselect' === $field['type'] ) ? '[]' : ''; ?>"
         id="<?php echo $instance->get_field_id($field['id']); ?>"
         style="<?php echo esc_attr( $field['css'] ); ?>"
         class="<?php echo esc_attr( $field['class'] ); ?>"
         data-required="<?php echo esc_attr( $field['required'] ); ?>"
         <?php echo $instance->get_field_custom_attributes($field['custom_attributes']); ?>
         <?php echo 'multiselect' === $field['type'] ? 'multiple="multiple"' : ''; ?>
         >
         <?php
         foreach ( $field['options'] as $key => $val ) {
            ?>
            <option value="<?php echo esc_attr( $key ); ?>"
               <?php
               if ( is_array( $field['value'] ) ) {
                  selected( in_array( (string) $key, $field['value'], true ), true );
               } else {
                  selected( $field['value'], (string) $key );
               }

               ?>
            ><?php echo esc_html( $val ); ?></option>
            <?php
         }
         ?>
      </select><?php echo $instance->get_field_description($field); ?>
   </td>
</tr>