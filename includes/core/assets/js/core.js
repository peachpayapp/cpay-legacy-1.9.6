( function($, woosa){

   if ( ! woosa ) {
      return;
   }

   var Ajax = woosa.ajax;
   var Translation = woosa.translation;
   var Prefix = woosa.prefix;
   var Util = woosa.util;

   var processCore = {

      /**
       * Initiates this module.
       */
      init: function(){

         //prevent the window which says "the changes may be lost"
         $(document).on('load click change', function(){
            window.onbeforeunload = null;
         });

         Util.elemSendAjaxCall( '[data-'+Prefix+'-action]' );

         this.capture_payment();

         this.toggle_test_mode();

      },


      /**
       * Sends request to capture the payment.
       * @since 1.1.0 - add additional confirmation window
       * @since 1.0.3
       */
      capture_payment: function(){

         $('[data-capture-order-payment]').on('click', function(){

            let _this = $(this),
               order_id = _this.attr('data-capture-order-payment');

            if(confirm(Translation.perform_action)){

               $.ajax({
                  url: Ajax.url,
                  method: 'POST',
                  data: {
                     action: Prefix+'_capture_payment',
                     security: Ajax.nonce,
                     order_id: order_id
                  },
                  beforeSend: function(){
                     _this.data('label', _this.text()).text(Translation.processing).prop('disabled', true);
                  },
                  success: function(res) {

                     window.location.reload();
                  }
               });
            }

         });
      },


      /**
       * Shows/hides the fields for test mode API credentials.
       */
      toggle_test_mode: function(){

         $('#'+Prefix+'_testmode').change( function() {
            if ( 'yes' === $(this).val() ) {
               $(this).closest('tbody').find( '.api_testmode_field' ).closest( 'tr' ).show();
            } else {
               $(this).closest('tbody').find( '.api_testmode_field' ).closest( 'tr' ).hide();
            }
         }).change();
      }

   };

   $( document ).ready( function() {
      processCore.init();
   });

})( jQuery, adn_util );