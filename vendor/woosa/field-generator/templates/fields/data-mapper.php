<?php
/**
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


$default_options = [
   'custom_field' => __('Product custom field', 'convesiopay-woocommerce'),
   'attribute'    => __('Product attribute', 'convesiopay-woocommerce'),
];
$options      = apply_filters(PREFIX .'\field_generator\render\data-mapper\options', Util::array($field)->get('options', $default_options), $field, $instance->get_context());
$source       = Util::array($field)->get('source');
$source_value = Util::array($field)->get('source_value');

$display_custom_field = selected($source, 'custom_field', $echo = false) ? '' : 'display:none;';
$display_custom_attribute = selected($source, 'attribute', $echo = false) ? '' : 'display:none;';

$field_identifier = strtolower( sanitize_title( $field['id'] ) );
?>
<tr valign="top">
   <th><label for="<?php echo $instance->get_field_id($field['id']); ?>"><?php echo esc_html( $field['title'] ); ?></label></th>
   <td class="forminp">
      <select id="<?php echo $instance->get_field_id($field['id']); ?>" name="<?php echo esc_attr( $field['id'] );?>" class="<?php echo esc_attr( $field['class'] ); ?>" data-<?php echo PREFIX;?>-has-extra-field="<?php echo esc_attr( $field_identifier );?>">
         <option value=""><?php _e('Please select', 'convesiopay-woocommerce');?></option>
         <?php foreach($options as $key => $label):?>
            <option value="<?php echo $key;?>" <?php selected($source, $key);?>><?php echo $label;?></option>
         <?php endforeach;?>
      </select>

      <?php if(isset($options['custom_field'])):?>
         <p data-<?php echo PREFIX;?>-extra-field-<?php echo esc_attr( $field_identifier );?>="custom_field" style="<?php echo $display_custom_field; ?>margin: 0">
            <label style="font-style:italic; font-size:12px;"><?php _e('Specify the custom field name', 'convesiopay-woocommerce');?></label><br/>
            <input type="text" name="<?php echo "{$field['id']}__custom_field_name";?>" value="<?php echo esc_attr( $source_value );?>">
         </p>
      <?php endif;?>

      <?php if(isset($options['attribute'])):?>
         <div data-<?php echo PREFIX;?>-extra-field-<?php echo esc_attr( $field_identifier );?>="attribute" style="<?php echo $display_custom_attribute; ?>">
            <p style="margin: 0;">
               <label style="font-style:italic; font-size:12px;"><?php _e('Specify below the slug or name of the attribute.', 'convesiopay-woocommerce');?></label>
            </p>
            <p style="margin: 0;">
               <input type="text" name="<?php echo "{$field['id']}__attribute_name";?>" value="<?php echo esc_attr( $source_value );?>">
            </p>
            <p style="font-style:italic; font-size:12px; display: block;margin: 0;">&bull; <?php _e('only the first value of the attribute values will be used.', 'convesiopay-woocommerce');?></p>
            <p style="font-style:italic; font-size:12px; display: block;margin: 0;">&bull; <?php _e('it does not work for custom attributes and variable products.', 'convesiopay-woocommerce');?></p>
         </div>
      <?php endif;?>
   </td>
   </tr>