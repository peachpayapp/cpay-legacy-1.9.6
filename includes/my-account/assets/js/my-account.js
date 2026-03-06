( function($, woosa){

   if ( ! woosa ) {
      return;
   }

   var Ajax = woosa.ajax;
   var Translation = woosa.translation;
   var Prefix = woosa.prefix;
   var Util = woosa.util;

   var myAccount = {

      /**
       * Initiates this module.
       */
      init: function(){

         this.remove_credit_card();

         this.remove_user_data();

      },


      /**
       * Sends request to remove the given card.
       * @since 1.0.3
       */
      remove_credit_card: function(){

         $('[data-remove-sci]').on('click', function(){

            let _this = $(this),
               reference = _this.attr('data-remove-sci');

            if(confirm(Translation.remove_card)){

               $.ajax({
                  url: Ajax.url,
                  method: 'POST',
                  data: {
                     action: Prefix+'_remove_card',
                     security: Ajax.nonce,
                     reference: reference
                  },
                  beforeSend: function(){
                     _this.closest('.'+Prefix+'-list-cards__item').fadeOut();
                  },
               });
            }

         });

      },


      /**
       * Sends request to remove user personal data.
       * @since 1.1.0
       */
      remove_user_data: function(){

         $('[data-remove-gdpr]').on('click', function(){

            let _this = $(this),
               order_id = _this.data('remove-gdpr');

            if(confirm(Translation.remove_gdpr)){

               $.ajax({
                  url: Ajax.url,
                  method: 'POST',
                  data: {
                     action: Prefix+'_remove_gdpr',
                     security: Ajax.nonce,
                     order_id: order_id
                  },
                  beforeSend: function(){
                     _this
                        .data('label', _this.text())
                        .text(Translation.processing)
                        .attr('disabled', true);

                     _this
                        .closest('div')
                        .find('.'+Prefix+'-error-text')
                        .remove();
                  },
                  success: function(res){

                     if(res.success){
                        window.location.reload();
                     }else{
                        $('<p class="'+Prefix+'-error-text">'+res.data.message+'</p>').insertAfter(_this.parent());
                     }

                  },
                  complete: function(){
                     _this
                        .text(_this.data('label'))
                        .attr('disabled', false);
                  }
               });
            }

         });

      }
   };

   $( document ).ready( function() {
      myAccount.init();
   });

})( jQuery, adn_util );