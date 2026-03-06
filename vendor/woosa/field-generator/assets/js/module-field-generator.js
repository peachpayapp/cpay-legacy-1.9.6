( function($, woosa){

   if ( ! woosa ) {
      return;
   }

   var Ajax = woosa.ajax;
   var Translation = woosa.translation;
   var Prefix = woosa.prefix;

   var moduleFieldGenerator = {

      init: function(){

         this.init_select2();
         this.init_quill();

      },


      /**
       * Init the select2.
       */
      init_select2(){
         if(jQuery.fn.select2){
            $('[data-'+Prefix+'-select2="yes"]').select2();
         }
      },


      /**
       * Init the Quill editor.
       */
      init_quill: function(){

         if (typeof Quill !== 'undefined') {

            $('[data-'+Prefix+'-editor-input]').each(function(){

               let _this    = $(this),
                  source_id = _this.attr('data-'+Prefix+'-editor-input'),
                  textarea  = $('[data-'+Prefix+'-editor-value="'+source_id+'"]');

               let editor = new Quill(_this.get(0), {
                  modules: {
                     toolbar: [
                        [{ header: [1, 2, 3, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }]
                     ]
                  },
                  theme: 'snow'  // or 'bubble'
               });

               var editor_value = editor.root.innerHTML;

               if('' === editor.getText().trim()){
                  editor_value = '';
               }

               textarea.html(editor_value);

               editor.on('text-change', function() {

                  var editor_value = editor.root.innerHTML;

                  if('' === editor.getText().trim()){
                     editor_value = '';
                  }

                  textarea.html(editor_value);
               });
            });
         }

      },

   };

   woosa.init_select2 = function(){
      moduleFieldGenerator.init_select2()
   };
   woosa.init_quill = function(){
      moduleFieldGenerator.init_quill()
   };

   $( document ).ready( function() {
      moduleFieldGenerator.init();
   });


})( jQuery, adn_module_field_generator );