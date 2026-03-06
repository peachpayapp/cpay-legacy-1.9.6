( function($, woosa){

   if ( ! woosa ) {
      return;
   }

   var Ajax = woosa.ajax;
   var Translation = woosa.translation;
   var Prefix = woosa.prefix;

   var moduleCore = {

      init: function(){

         //prevent the window which says "the changes may be lost"
         $(document).on('load click change', function(){
            window.onbeforeunload = null;
         });

         this.init_tablist();
         this.init_conditional_fields();
         this.save_changes();
         this.resize_tb();

      },


      /**
       * Shows/hides the tab content.
       */
      init_tablist: function(){

         let process_tab = function(tab){
            let index = tab.index(),
               tablist = tab.parent(),
               panelWrapper = tablist.next('[data-'+Prefix+'-tabpanel]');

            tablist.children().removeClass('nav-tab-active');
            tab.addClass('nav-tab-active');

            panelWrapper.children().hide();
            panelWrapper.children().eq(index).fadeIn();
         };


         //on load
         if(location.hash != ''){
            process_tab( $(document).find('[data-'+Prefix+'-tablist] > [data-anchor='+location.hash+']') );
         }

         //on refresh with URL hash
         $(document).on(Prefix + '-refresh-tablist', function(){

            if(location.hash != ''){
               process_tab( $(document).find('[data-'+Prefix+'-tablist] > [data-anchor='+location.hash+']') );
            }

         });

         //on click
         $(document).on('click', '[data-'+Prefix+'-tablist] > div', function(){

            let tab = $(this),
               anchor = tab.attr('data-anchor');

            process_tab(tab);

            history.pushState({}, "", anchor);

         });
      },


      /**
       * display conditional fields
       */
      init_conditional_fields: function(){

         $(document).on('change', '[data-'+Prefix+'-has-extra-field]', function(){

            let _this = $(this),
               value = _this.val().toLowerCase(),
               parent = _this.attr('data-'+Prefix+'-has-extra-field');

            if ( 'checkbox' === _this.prop( "type" ) && ! _this.is( ':checked' ) ) {
               value = 'no';
            }

            $('[data-'+Prefix+'-extra-field-'+parent+']').hide();
            $('[data-'+Prefix+'-extra-field-'+parent+'="'+value+'"]').fadeIn();
         });

      },


      /**
       * Processes the save action.
       */
      save_changes: function(){

         $(document).on('click', 'button[data-' + Prefix + '_ajax-save]', this, function(e){

            var _this = $(this),
               obj    = e.data,
               props  = JSON.parse(_this.attr('data-' + Prefix + '_ajax-save')),
               fields = $(props.section.join(', ')).find('select, textarea, input').serialize();

            $.ajax({
               url: Ajax.url,
               method: "POST",
               data: {
                  action: Prefix + '_' + props.action,
                  security: Ajax.nonce,
                  fields: fields,
               },
               beforeSend: function(){

                  if($('#TB_ajaxContent').length > 0){

                     $('#TB_ajaxContent').block({
                        message: null,
                        overlayCSS: {
                           background: '#fff',
                           opacity: 0.6
                        }
                     });

                  }else{

                     $('#wpcontent').block({
                        message: null,
                        overlayCSS: {
                           background: '#fff',
                           opacity: 0.6
                        }
                     });
                  }

                  _this.next('.ajax-response').remove();

                  _this.closest('#mainform').find(':input').prop('disabled', true);
                  _this.closest('#TB_ajaxContent').find(':input').prop('disabled', true);

               },
               success: function(res) {

                  if(res.success){

                     if(props.refresh){
                        window.location.reload();
                     }else{

                        $('<p class="ajax-response success">'+res.data.message+'</p>').insertAfter(_this);

                        $('body').trigger(Prefix + '-ajax-save-success', {'props': props, 'res': res});
                     }

                  }else{

                     if(res.data){

                        $('<p class="ajax-response error">'+res.data.message+'</p>').insertAfter(_this);

                        $('body').trigger(Prefix + '-ajax-save-failure', {'props': props, 'res': res});
                     }

                     _this.text( _this.data('label') );
                  }
               },
               complete: function(){

                  $('#wpcontent').unblock();
                  $('#TB_ajaxContent').unblock();

                  _this.closest('#mainform').find(':input').not('.always_disabled').prop('disabled', false);
                  _this.closest('#TB_ajaxContent').find(':input').not('.always_disabled').prop('disabled', false);

               }
            });
         });
      },


      /**
       * Corrects the size of TB.
       */
      resize_tb: function(interval){

         var wpbody = jQuery(document).find('#wpbody-content'),
            popupW = parseInt(wpbody.width() * 80/100, 10);

         jQuery(document).find('#TB_window').css({
            marginLeft: '-' + parseInt((popupW / 2),10) + 'px',
            width: parseInt(popupW,10) + 'px',
         });
         jQuery(document).find('#TB_ajaxContent').css({
            width: parseInt(popupW-30,10) + 'px',
         });

         if(jQuery(document).find('#TB_window').css('visibility') == 'visible' && parseInt(jQuery(document).find('#TB_window').width(),10) == popupW){
            clearInterval(interval);
         }

      },

   };

   woosa.util = {

      resize_tb: function(interval = null) {
         moduleCore.resize_tb(interval);
      }
   };

   $( document ).ready( function() {
      moduleCore.init();
   });


})( jQuery, adn_module_core );