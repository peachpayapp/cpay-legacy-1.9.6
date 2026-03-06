<?php
/**
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


$custom_attributes = $instance->get_field_custom_attributes($field['custom_attributes']);
$price_addition    = Util::array($field)->get('price_addition', Option::get('price_addition'));
$display_addition  = 'yes' === $field['value'] ? 'display:block;' : 'display:none;';
?>

<tr valign="top" class="<?php echo esc_attr( $is_disabled );?>">
   <td colspan="2" class="pl-0 forminp forminp-toggle">
      <fieldset>
         <div class="toggle-wrap">
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
               data-<?php echo PREFIX;?>-has-extra-field="<?php echo esc_attr( $instance->get_field_id($field['id']) );?>"
            />

            <label class="switch" for="<?php echo esc_attr( $instance->get_field_id( $field['id'] ) ); ?>">
               <span class="slider"></span>
            </label>
         </div>
         <div class="toggle-info">
            <label for="<?php echo esc_attr( $instance->get_field_id( $field['id'] ) ); ?>">
               <div class="titledesc"><?php echo esc_html( $field['title'] ); ?></div>
            </label>
            <?php echo $instance->get_field_description($field);?>
         </div>
         <div class="pt-10 pl-35" data-<?php echo PREFIX;?>-extra-field-<?php echo esc_attr( $instance->get_field_id($field['id']) );?>="yes" style="<?php echo esc_attr( $display_addition );?>">
            <input type="text" name="<?php echo esc_attr( $instance->get_field_name('price_addition') ); ?>" value="<?php echo esc_attr( $price_addition );?>" placeholder="e.g. 10% or 10.00" <?php echo $instance->get_field_custom_attributes($field['custom_attributes']); ?>>
            <p class="description" style="font-style:italic; font-size:12px;"><?php _e('Adjust the price (e.g. "10" for fixed amount or "10%" for percentage amount)', 'convesiopay-woocommerce');?></p>
         </div>
      </fieldset>
   </td>
</tr>
