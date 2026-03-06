<?php
/**
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


$custom_attributes = $instance->get_field_custom_attributes($field['custom_attributes']);
?>
<tr valign="top" class="<?php echo esc_attr( $is_disabled );?>">
   <td colspan="2" class="pl-0 forminp forminp-<?php echo esc_attr( $instance->get_field_type($field['type']) ); ?>">
      <fieldset>
         <div class="<?php echo esc_attr( $instance->get_field_type($field['type']) ); ?>-wrap">
            <?php if(strpos($custom_attributes, 'disabled') === false):?>
               <input type="hidden" name="<?php echo esc_attr( $instance->get_field_name($field['id']) ); ?>" value="no">
            <?php else:?>
               <input type="hidden"
                  name="<?php echo esc_attr( $instance->get_field_name($field['id']) ); ?>"
                  value="<?php echo esc_attr( $field['value'] );?>">
            <?php endif;?>
            <input
               name="<?php echo esc_attr( $instance->get_field_name($field['id']) ); ?>"
               id="<?php echo esc_attr( $instance->get_field_id($field['id']) ); ?>"
               type="checkbox"
               class="checkbox <?php echo esc_attr( isset( $field['class'] ) ? $field['class'] : '' ); ?>"
               value="yes"
               <?php echo $instance->get_field_custom_attributes($field['custom_attributes']); ?>
               <?php checked( $field['value'], 'yes' ); ?>
            />

            <label class="switch" for="<?php echo esc_attr( $instance->get_field_id( $field['id'] ) ); ?>">
               <span class="slider"></span>
            </label>
         </div>
         <div class="<?php echo esc_attr( $instance->get_field_type($field['type']) ); ?>-info">
            <label for="<?php echo esc_attr( $instance->get_field_id( $field['id'] ) ); ?>">
               <div class="titledesc"><?php echo esc_html( $field['title'] ); ?></div>
            </label>
            <?php echo $instance->get_field_description($field);?>
         </div>
      </fieldset>
   </td>
</tr>