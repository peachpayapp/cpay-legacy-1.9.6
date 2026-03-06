<?php
/**
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


$periods      = array(
   'days'   => __( 'Day(s)', 'convesiopay-woocommerce' ),
   'weeks'  => __( 'Week(s)', 'convesiopay-woocommerce' ),
   'months' => __( 'Month(s)', 'convesiopay-woocommerce' ),
   'years'  => __( 'Year(s)', 'convesiopay-woocommerce' ),
);
$option_value = wc_parse_relative_date_option( $field['value'] );
?>
<tr valign="top" class="<?php echo $is_disabled;?>">
   <th scope="row" class="titledesc">
      <label for="<?php echo $instance->get_field_id($field['id']); ?>"><?php echo esc_html( $field['title'] ); ?> <?php echo $instance->get_field_tooltip($field['desc_tip']);?></label>
   </th>
   <td class="forminp">
   <input
         name="<?php echo esc_attr( $field['id'] ); ?>[number]"
         id="<?php echo $instance->get_field_id($field['id']); ?>"
         type="number"
         style="width: 80px;"
         value="<?php echo esc_attr( $option_value['number'] ); ?>"
         class="<?php echo esc_attr( $field['class'] ); ?>"
         placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>"
         data-required="<?php echo esc_attr( $field['required'] ); ?>"
         step="1"
         min="1"
         <?php echo $instance->get_field_custom_attributes($field['custom_attributes']); ?>
      />&nbsp;
      <select name="<?php echo esc_attr( $field['id'] ); ?>[unit]" style="width: auto;">
         <?php
         foreach ( $periods as $period_key => $period_label ) {
            echo '<option value="' . esc_attr( $period_key ) . '"' . selected( $option_value['unit'], $period_key, false ) . '>' . esc_html( $period_label ) . '</option>';
         }
         ?>
      </select>
      <?php echo $instance->get_field_description($field); ?>
   </td>
</tr>