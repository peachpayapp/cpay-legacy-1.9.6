( function($, woosa){

   if ( ! woosa ) {
      return;
   }

   var Ajax = woosa.ajax;
   var Translation = woosa.translation;
   var Prefix = woosa.prefix;

   woosa.util = {

      blockSection: function(elem) {
         jQuery(elem).block({
            message: null,
            overlayCSS: {
               background: '#fff',
               opacity: 0.6
            }
         });
      },


      unBlockSection: function(elem) {
         jQuery(elem).unblock();
      },


      isJson: function(str) {
         try{
            JSON.parse(str);
         }catch (e){
            //Error
            //JSON is not okay
            return false;
         }

         return true;
      },


      elemSendAjaxCall: function(target){

         jQuery(function($){

            var is_processing = false;

            $(document).on('click', target, function(e){

               e.preventDefault();

               var btn = $(this),
                  action = btn.attr('data-'+Prefix+'-action'),
                  extra = woosa.util.isJson(btn.attr('data-'+Prefix+'-extra')) ? JSON.parse(btn.attr('data-'+Prefix+'-extra')) : btn.attr('data-'+Prefix+'-extra'),
                  processing_label = Translation.processing,
                  parentElem = btn.parent(),
                  fields = btn.closest('#mainform').find(':input').serialize();

               if(is_processing) return;

               $.ajax({
                  url: Ajax.url,
                  method: 'POST',
                  data: {
                     action: Prefix+'_'+action,
                     security: Ajax.nonce,
                     extra: extra,
                     fields: fields,
                  },
                  beforeSend: function(){

                     is_processing = true;

                     woosa.util.blockSection('#wpcontent');

                     btn.data('label', btn.text());
                     btn.text(processing_label).attr('disabled', true);

                     parentElem.parent().find('.'+Prefix+'-error-text').remove();
                  },
                  success: function(res){

                     if(res.success){
                        window.location.reload();
                     }else{
                        $('<p class="'+Prefix+'-error-text">'+res.data.message+'</p>').insertAfter(parentElem);
                        btn.text( btn.data('label') ).attr('disabled', false);
                        woosa.util.unBlockSection('#wpcontent');
                     }
                  },
                  complete: function(){

                     is_processing = false;
                  }
               });
            });

         });

      },


      debugLog: function(msg){
         if(woosa.debug){
            console.log('%cDEBUG LOG: '+msg, "color: orange");
         }
      },

   };

})( jQuery, adn_util );