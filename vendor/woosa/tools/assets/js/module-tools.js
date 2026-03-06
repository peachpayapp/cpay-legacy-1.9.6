( function($, woosa){

   if ( ! woosa ) {
      return;
   }

   var Ajax = woosa.ajax;
   var Translation = woosa.translation;
   var Prefix = woosa.prefix;

   var moduleTools = {

      init: function(){

         this.run_tool();
      },

      run_tool: function(){

         $(document).on('click', '[data-'+Prefix+'-run-tool]', function(e){

            var _this = $(this),
               btn_parent = _this.parent(),
               tool_id = _this.attr('data-'+Prefix+'-run-tool');

            if( ! window.confirm('Are you sure you want to run this tool ('+tool_id+')?') ) {
               return;
            }

            $.ajax({
               url: Ajax.url,
               method: "POST",
               data: {
                  action: Prefix + '_run_tool',
                  security: Ajax.nonce,
                  tool_id: tool_id,
               },
               beforeSend: function(){

                  $('#wpcontent').block({
                     message: null,
                     overlayCSS: {
                        background: '#fff',
                        opacity: 0.6
                     }
                  });

                  btn_parent.find('.ajax-response').remove();

                  _this.data('label', _this.text()).text(Translation.processing).prop('disabled', true);

               },
               success: function(res) {

                  if(res.success){

                     if(res.data.reload){

                        return window.location.reload();

                     }else{

                        btn_parent.append('<p class="ajax-response success">'+res.data.message+'</p>');
                     }

                  }else{
                     btn_parent.append('<p class="ajax-response error">'+res.data.message+'</p>');
                  }

                  $('#wpcontent').unblock();

                  _this.text( _this.data('label') ).prop('disabled', false);
               }
            });

         });

      }

   };

   $( document ).ready( function() {
      moduleTools.init();
   });


})( jQuery, adn_module_core );