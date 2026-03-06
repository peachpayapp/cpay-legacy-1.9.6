<?php
/**
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


$units = Util::array($field)->get('units', []);
?>
<tr valign="top" class="<?php echo $is_disabled;?>">
   <th scope="row" class="titledesc">
      <label for="<?php echo $instance->get_field_id($field['id']); ?>"><?php echo esc_html( $field['title'] ); ?> <?php echo $instance->get_field_tooltip($field['desc_tip']);?></label>
   </th>
   <td class="forminp forminp-<?php echo esc_attr( sanitize_title( $field['type'] ) ); ?>">
      <input
         name="<?php echo $instance->get_field_name( $field['id'] ); ?>"
         id="<?php echo $instance->get_field_id($field['id']); ?>"
         type="<?php echo $instance->get_field_type($field['type']); ?>"

         <?php if ( $field['css'] ) : ?>
            style="<?php echo esc_attr( $field['css'] ); ?>"
         <?php endif; ?>

         <?php if ( $field['value'] || $field['value'] == 0 ) : ?>
            value="<?php echo esc_attr( $field['value'] ); ?>"
         <?php endif; ?>

         <?php if ( $field['class'] ) : ?>
            class="<?php echo esc_attr( $field['class'] ); ?>"
         <?php endif; ?>

         <?php if ( $field['placeholder'] ) : ?>
            placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>"
         <?php endif; ?>

         <?php if ( $field['required'] ) : ?>
            data-required="<?php echo esc_attr( $field['required'] ); ?>"
         <?php endif; ?>

         <?php echo $instance->get_field_custom_attributes($field['custom_attributes']); ?>
      />

      <?php if('number' === $field['type'] && ! empty($units)):?>
         <select id="<?php echo $instance->get_field_id($field['id']); ?>_unit" name="<?php echo esc_attr( $field['id'] ); ?>_unit">
            <option value=""><?php _e('Please select', 'convesiopay-woocommerce');?></option>
            <?php foreach($units as $unit => $unit_label):?>
               <option value="<?php echo $unit;?>" <?php selected(Util::array($field)->get('value_unit'), $unit);?>><?php echo $unit_label;?></option>
            <?php endforeach;?>
         </select>
      <?php endif;?>

      <?php if ( $field['suffix'] ) : ?>
         <?php echo esc_html( $field['suffix'] ); ?>
      <?php endif; ?>

      <?php echo $instance->get_field_description($field); ?>
   </td>
</tr>