( function($, woosa){

   if ( ! woosa ) {
      return;
   }

   var Ajax = woosa.ajax;
   var Translation = woosa.translation;

   var moduleAuthorization = {

      init: function(){

         //prevent the window which says "the changes may be lost"
         $(document).on('load click change', function(){
            window.onbeforeunload = null;
         });

         this.process_authorization();
      },

      process_authorization: function(){

         $(document).on('click', '[data-'+woosa.prefix+'-authorization]', this, function(e) {
            e.data.run_ajax({
               action: 'authorization',
               button: $(this),
            });
         });
      },

      run_ajax: function(props){

         var button    = props.button,
            btn_parent = button.parent(),
            form       = button.closest('form'),
            args       = this.JSON_parse(button.attr('data-'+woosa.prefix+'-'+props.action)),
            input      = form.find('select, textarea, input, button');

         $.ajax({
            url: Ajax.url,
            method: "POST",
            data: {
               action: woosa.prefix+'_process_'+props.action,
               security: Ajax.nonce,
               args: args,
               fields: input.serialize(),
            },
            beforeSend: function(){

               input.prop('disabled', true);

               button.data('label', button.text()).text(Translation.processing);

               jQuery('#wpcontent').block({
                  message: null,
                  overlayCSS: {
                     background: '#fff',
                     opacity: 0.6
                  }
               });

               form.find('.ajax-response').remove();
            },
            success: function(res) {

               if(res.success){
                  window.location.reload();
               }else{

                  $('<p class="ajax-response error">'+res.data.message+'</p>').insertAfter(btn_parent);

                  button.text( button.data('label') );

                  input.prop('disabled', false);

                  jQuery('#wpcontent').unblock();
               }

            }
         });

      },

      JSON_parse: function(str){
         try{
            return JSON.parse(str);
         }catch (e){
            return str;
         }
      },

   };

   $( document ).ready( function() {
      moduleAuthorization.init();
   });


})( jQuery, adn_module_core );