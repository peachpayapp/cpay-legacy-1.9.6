<?php
/**
 * Module Field Generator
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;

class Module_Field_Generator {


   /**
    * The context in which the fields are rendered.
    *
    * @var string
    */
   protected $context = '';


   /**
    * List of fields.
    *
    * @var array
    */
   protected $fields = [];


   /**
    * The array name that each field will be added to.
    *
    * @var string
    */
   protected $array_name = '';



   /**
    * Sets the list of fields and the context.
    * [
    *    [
    *       'id'                => 'id_of_the_field',
    *       'title'             => 'label/title of the field',
    *       'name'              => 'label/title of the field',
    *       'type'              => 'text',
    *       'required'          => 1|0
    *       'options'           => [] //for `select`, `radio`
    *       'custom_attributes' => [],
    *    ]
    * ]
    * @param array $fields
    * @param string $context
    * @return void
    */
   public function set_fields( array $fields, string $context = '' ){

      $this->fields  = $fields;
      $this->context = $context;

   }



   /**
    * Sets the array name where the field to be add to.
    *
    * @param string $array_name
    * @return void
    */
   public function set_array_name(string $array_name){
      $this->array_name = $array_name;
   }



   /**
    * Retrieves the list of fields.
    *
    * @return array
    */
   public function get_fields(){
      return apply_filters(PREFIX . '\field_generator\fields', $this->fields, $this->context);
   }



   /**
    * Gets the context.
    *
    * @return string
    */
   public function get_context(){
      return $this->context;
   }



   /**
    * Helper function to get the formatted description for a
    * given form field.
    *
    * @param  array $field The field value array.
    * @return string The field description.
    */
   public function get_field_description( array $field ) {

      $description  = Util::array($field)->get('desc');

      if( ! empty($description) ){

         if ( $description && in_array( $field[ 'type' ], [ 'textarea', 'radio' ], true ) ) {
            $description = '<p style="margin-top:0">' . wp_kses_post( $description ) . '</p>';
         } elseif ( $description && in_array( $field['type'], [ 'checkbox' ], true ) ) {
            $description = wp_kses_post( $description );
         } elseif ( $description ) {
            $description = '<p class="description">' . wp_kses_post( $description ) . '</p>';
         }
      }

      $description = apply_filters( PREFIX . '\field_generator\field_description', $description, $field, $this->context );

      return $description;

   }



   /**
    * Gets the field desc_tip.
    *
    * @param string $desc_tip
    * @return string
    */
   public function get_field_tooltip(string $desc_tip){

      if( ! empty($desc_tip) ){
         $desc_tip = wc_help_tip($desc_tip);
      }

      return $desc_tip;
   }



   /**
    * Gets the field id.
    *
    * @param string $id
    * @return string
    */
   public function get_field_id(string $id){

      if( ! empty($this->array_name) ){
         $id = md5($this->array_name) . '-' . $id;
      }

      return esc_attr($id);
   }



   /**
    * Gets the field name.
    *
    * @param string $name
    * @return string
    */
   public function get_field_name(string $name){

      if( ! empty($this->array_name) ){
         $name = "{$this->array_name}[$name]";
      }

      return esc_attr($name);
   }



   /**
    * Gets the field type.
    *
    * @param string $type
    * @return string
    */
   public function get_field_type(string $type){
      return esc_attr($type);
   }



   /**
    * Gets the field custom attributes.
    *
    * @param array $custom_attributes
    * @return string
    */
   public function get_field_custom_attributes(array $custom_attributes){

      $attrs = '';

      foreach ( $custom_attributes as $attribute => $attribute_value ) {
         $attrs .= esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
      }

      return $attrs;
   }



   /**
    * Set the default keys in the field before output it.
    *
    * @param array $field
    * @return array|false
    */
   protected function pre_process( array $field ) {

      $field = apply_filters( PREFIX . '\field_generator\pre_process\field', $field, $this->context );

      if(is_array($field)){

         if ( ! isset( $field['type'] ) ) {

            $field = false;

         }else{

            $field['id']                = Util::array($field)->get('id');
            $field['title']             = Util::array($field)->get('title', Util::array($field)->get('name'));
            $field['class']             = 'widefat ' . Util::array($field)->get('class');
            $field['css']               = Util::array($field)->get('css');
            $field['default']           = Util::array($field)->get('default');
            $field['desc']              = Util::array($field)->get('desc');
            $field['desc_tip']          = Util::array($field)->get('desc_tip', '');
            $field['placeholder']       = Util::array($field)->get('placeholder');
            $field['suffix']            = Util::array($field)->get('suffix');
            $field['required']          = Util::array($field)->get('required');
            $field['custom_attributes'] = Util::array($field)->get('custom_attributes', []);
            $field['value']             = Util::array($field)->get('value', Option::get($field['id'], $field['default']));
         }

      }else{

         $field = false;
      }

      return $field;
   }



   /**
    * Displays the output of the given fields.
    * Loops through the array and outputs each field.
    *
    * @return string|void
    */
   public function render() {

      foreach ( $this->get_fields() as $field ) {

         $field = $this->pre_process( $field, $this->context );
         $type  = Util::array($field)->get('type');

         if ( false === $field ) {
            continue;
         }

         $is_disabled = strpos($this->get_field_custom_attributes($field['custom_attributes']), 'disabled') !== false ? 'is-field-disabled' : '';

         switch ( $type ) {

            case 'title':

               $section_id = Util::unprefix( $this->get_field_id($field['id']) );

               echo '<div class="page-section ' . $section_id . '">';

               if ( ! empty( $field['title'] ) ) {
                  echo '<h2>' . esc_html( $field['title'] ) . '</h2>';
               }

               if ( ! empty( $field['desc'] ) ) {
                  echo '<div id="' . $section_id . '-description">';
                  echo wp_kses_post( wpautop( wptexturize( $field['desc'] ) ) );
                  echo '</div>';
               }

               echo '<div class="page-section-content ' . $section_id . '">';
               echo '<table class="form-table">';

               break;

            // Section Ends.
            case 'sectionend':
               echo '</table>';
               echo '</div>';//.field-section-content
               echo '</div>';//.page-section
               break;

            // Standard text inputs and subtypes like 'number'.
            case 'text':
            case 'password':
            case 'datetime':
            case 'datetime-local':
            case 'date':
            case 'month':
            case 'time':
            case 'week':
            case 'number':
            case 'email':
            case 'url':
            case 'tel':

               echo Util::get_template('text.php', [
                  'field'    => $field,
                  'is_disabled' => $is_disabled,
                  'instance' => $this,
               ], dirname(dirname(__FILE__)), 'field-generator/templates/fields');

               break;

            default:

               $output = Util::get_template("{$type}.php", [
                  'field'    => $field,
                  'is_disabled' => $is_disabled,
                  'instance' => $this,
               ], dirname(dirname(__FILE__)), 'field-generator/templates/fields');

               if(empty($output)){

                  do_action( PREFIX . '\field_generator\render\\' . $field['type'], $field, $this->context );

               }else{

                  echo $output;
               }
         }
      }

   }
}