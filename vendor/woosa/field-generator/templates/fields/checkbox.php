<?php
/**
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


$visibility_class  = [];
$custom_attributes = $instance->get_field_custom_attributes($field['custom_attributes']);

if ( ! isset( $field['hide_if_checked'] ) ) {
   $field['hide_if_checked'] = false;
}
if ( ! isset( $field['show_if_checked'] ) ) {
   $field['show_if_checked'] = false;
}
if ( 'yes' === $field['hide_if_checked'] || 'yes' === $field['show_if_checked'] ) {
   $visibility_class[] = 'hidden_option';
}
if ( 'option' === $field['hide_if_checked'] ) {
   $visibility_class[] = 'hide_options_if_checked';
}
if ( 'option' === $field['show_if_checked'] ) {
   $visibility_class[] = 'show_options_if_checked';
}

if ( ! isset( $field['checkboxgroup'] ) || 'start' === $field['checkboxgroup'] ) {
   ?>
      <tr valign="top" class="<?php echo esc_attr( implode( ' ', $visibility_class ) ); ?> <?php echo esc_attr( $is_disabled );?>">
         <th scope="row" class="titledesc"><label><?php echo esc_html( $field['title'] ); ?></label></th>
         <td class="forminp forminp-checkbox">
            <fieldset>
   <?php
} else {
   ?>
      <fieldset class="<?php echo esc_attr( implode( ' ', $visibility_class ) ); ?>">
   <?php
}

if ( ! empty( $field['title'] ) ) {
   ?>
      <legend class="screen-reader-text"><span><?php echo esc_html( $field['title'] ); ?></span></legend>
   <?php
}

?>
   <label for="<?php echo esc_attr( $instance->get_field_id($field['id']) ); ?>">
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
         class="<?php echo esc_attr( isset( $field['class'] ) ? $field['class'] : '' ); ?>"
         value="yes"
         <?php checked( $field['value'], 'yes' ); ?>
         <?php echo $custom_attributes; ?>
      /> <?php echo $instance->get_field_description($field); ?>
   </label> <?php echo $instance->get_field_tooltip($field['desc_tip']);?>
<?php

if ( ! isset( $field['checkboxgroup'] ) || 'end' === $field['checkboxgroup'] ) {
   ?>
            </fieldset>
         </td>
      </tr>
   <?php
} else {
   ?>
      </fieldset>
   <?php
}