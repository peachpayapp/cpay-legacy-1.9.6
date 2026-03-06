( function($, woosa){

   if ( ! woosa ) {
      return;
   }

   var Ajax = woosa.ajax;
   var Translation = woosa.translation;

   var ProcessLicense = {

      init: function(){

         //prevent the window which says "the changes may be lost"
         $(document).on('load click change', function(){
            window.onbeforeunload = null;
         });

         this.submit();
      },

      submit: function(){

         $(document).on('click', '[data-'+woosa.prefix+'-license]', function(e) {

            var _this = $(this),
               mode = _this.attr('data-'+woosa.prefix+'-license'),
               section = _this.parent(),
               input = section.find('input[type="text"]'),
               fields = section.find('select, textarea, input, button');

            $.ajax({
               url: Ajax.url,
               method: "POST",
               data: {
                  action: woosa.prefix+'_license_submission',
                  mode: mode,
                  security: Ajax.nonce,
                  key: input.val()
               },
               beforeSend: function(){

                  fields.prop('disabled', true);

                  jQuery('#wpcontent').block({
                     message: null,
                     overlayCSS: {
                        background: '#fff',
                        opacity: 0.6
                     }
                  });

                  section.find('.ajax-response').remove();
               },
               success: function(res) {

                  var el_class = res.success ? 'success' : 'error';

                  if(res.data && res.data.message){
                     section.append('<p class="ajax-response '+el_class+'">'+res.data.message+'</p>');

                     fields.prop('disabled', false);

                     jQuery('#wpcontent').unblock();

                  }else{

                     window.location.reload();
                  }

               },
            });

         });
      },

   };

   $( document ).ready( function() {
      ProcessLicense.init();
   });


})( jQuery, adn_license );