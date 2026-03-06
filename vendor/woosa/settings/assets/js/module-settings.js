( function($, woosa){

   if ( ! woosa ) {
      return;
   }

   var Ajax = woosa.ajax;
   var Translation = woosa.translation;
   var Prefix = woosa.prefix;

   var moduleSettings = {

      init: function(){

         $('.woocommerce-help-tip').tipTip( {
            'attribute' : 'data-tip',
            'fadeIn'    : 50,
            'fadeOut'   : 50,
            'delay'     : 0
         });

         this.save_changes();
         this.slide_sections();

      },


      /**
       * Saves the chanches.
       */
      save_changes: function(){

         $(document).on('click', '[data-submit-button="' + Prefix + '_save_settings"]', function(e){

            var button     = $(this),
               form       = button.closest('form'),
               input      = form.find('select, textarea, input, button'),
               btn_parent = button.parent();

            $.ajax({
               url: Ajax.url,
               method: "POST",
               data: {
                  action: Prefix + '_save_settings_page',
                  security: Ajax.nonce,
                  fields: input.serialize(),
               },
               beforeSend: function(){

                  $('#wpcontent').block({
                     message: null,
                     overlayCSS: {
                        background: '#fff',
                        opacity: 0.6
                     }
                  });

                  btn_parent.next('.ajax-response').remove();

                  input.prop('disabled', true);

                  button.data('label', button.text()).text(Translation.processing);

               },
               success: function(res) {

                  if(res.success){

                     $('<p class="ajax-response success">'+res.data.message+'</p>').insertAfter(btn_parent);

                  }else{

                     $('<p class="ajax-response error">'+res.data.message+'</p>').insertAfter(btn_parent);
                  }
               },
               complete: function(){

                  $('#wpcontent').unblock();

                  input.not('.always_disabled').prop('disabled', false);

                  button.text( button.data('label') );
               }
            });
         });

      },


      /**
       * Slide up/down the fields sections.
       */
      slide_sections: function () {

         $(document).on('click', '[data-' + Prefix + '-collapsible-state]', function(e){

            var header = $(this),
               wrapper = header.closest('.collapsible-wrap'),
               content = wrapper.find('.collapsible-content'),
               state   = header.attr('data-' + Prefix + '-collapsible-state');

            moduleSettings.slideup_actives(wrapper);

            setTimeout(function(){
               $('html, body').animate({
                  scrollTop: wrapper.offset().top - 40
               }, 900);
            }, 400);

            if ('active' === state) {

               content.slideUp(400, function(){

                  wrapper.removeClass('active');
                  wrapper.addClass('closed');
                  header.attr('data-' + Prefix + '-collapsible-state', 'closed');
               });

               header.find('.wch-collapse').show();
               header.find('.wch-minimize').hide();

            } else if ('closed' === state) {

               content.slideDown();

               wrapper.removeClass('closed');
               wrapper.addClass('active');

               header.find('.wch-collapse').hide();
               header.find('.wch-minimize').show();
               header.attr('data-' + Prefix + '-collapsible-state', 'active');

            }

         });
      },


      /**
       * Slides up all active excepting the current that is clicked.
       */
      slideup_actives: function(current){

         $('.collapsible-wrap.active').each(function(){

            var wrapper = $(this);

            if(wrapper.index() != current.index()){

               var header = wrapper.find('.collapsible-header')
                  content = wrapper.find('.collapsible-content');

               content.slideUp(400, function(){

                  wrapper.removeClass('active');
                  wrapper.addClass('closed');
                  header.attr('data-' + Prefix + '-collapsible-state', 'closed');
               });

               header.find('.wch-collapse').show();
               header.find('.wch-minimize').hide();
            }
         });
      }

   };

   $( document ).ready( function() {
      moduleSettings.init();
   });


})( jQuery, adn_module_core );