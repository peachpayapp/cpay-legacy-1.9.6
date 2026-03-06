( function($, woosa){

   if ( ! woosa ) {
      return;
   }

   var Ajax = woosa.ajax;
   var Translation = woosa.translation;

   var countrySelection = {

      init: function(){
         this.toggle_connection();
         this.load_items();
      },

      toggle_connection: function(){

         let target = 'data-'+woosa.prefix+'-sm-action';

         $(document).on('click', '['+target+']', function(event){

            event.preventDefault();

            let _this     = $(this),
               action     = _this.attr(target),
               data       = {
                  action  : woosa.prefix+'_sm_'+action,
                  security: Ajax.nonce,
                  url     : window.location.href
               },
               cat        = [],
               trail      = [],
               term_id    = _this.closest('tr').attr('data-'+woosa.prefix+'-cm-term-id'),
               cat_id     = _this.closest('tr').attr('data-'+woosa.prefix+'-cm-category-id'),
               info_sec   = _this.closest('table').find('[data-'+woosa.prefix+'-cm-info]'),
               box        = _this.closest('[data-'+woosa.prefix+'-cm-box]');

            if('connect' === action){
               box.find('[data-'+woosa.prefix+'-cs-input]').each(function(){
                  cat.push($(this).val());
               });
               box.find('[data-'+woosa.prefix+'-cs-trail]').each(function(){
                  trail.push($(this).html());
               });

               Object.assign(data, {cat: cat, trail: trail});

            }else{

               Object.assign(data, {term_id: term_id, cat_id: cat_id});
            }

            $.ajax({
               url: Ajax.url,
               method: "POST",
               data: data,
               beforeSend: function(){

                  _this.attr('disabled', true);

                  info_sec.find('.ajax-response').remove();

                  box.block({
                     message: null,
                     overlayCSS: {
                        background: '#fff',
                        opacity: 0.6
                     }
                  });
               },
               success: function(res){

                  if(res.success){

                     window.location.href = res.data.redirect_url;

                  }else if(res.data.message){

                     info_sec.append('<p class="ajax-response error">'+res.data.message+'</p>');

                     _this.attr('disabled', false);

                     box.unblock();

                  }

               },
            });

         });
      },

      load_items: function(){

         let target = 'data-'+woosa.prefix+'-load-country-items';

         $(document).on('click', '['+target+']', this, function(event){

            event.preventDefault();

            let _this  = $(event.target),
               box     = _this.closest('[data-'+woosa.prefix+'-cs-box]'),
               source  = box.attr('data-'+woosa.prefix+'-cs-box'),
               level   = box.attr('data-'+woosa.prefix+'-cs-level'),
               list    = box.find('[data-'+woosa.prefix+'-cs-list]'),
               trail   = box.find('[data-'+woosa.prefix+'-cs-trail]'),
               input   = box.find('[data-'+woosa.prefix+'-cs-input]'),
               item_id = _this.attr(target);

            _this.attr('disabled', true);

            input.val('');

            box.block({
               message: null,
               overlayCSS: {
                  background: '#fff',
                  opacity: 0.6
               }
            });

            event.data.get_template(item_id, source, level).then(function(res){

               if(res.success && res.data.list){

                  _this.hide();

                  trail.html(res.data.trail);

                  list.html(res.data.list).show();

                  if(res.data.last || _this.hasClass('cs-select-item')){
                     list.hide();
                     input.val(item_id);
                  }

               }else{
                  _this.show();
               }

               _this.attr('disabled', false);

               box.unblock();
            });

         });

      },


      get_template:function(item_id = 0, source, level){

         return $.ajax({
            url: Ajax.url,
            method: "POST",
            data: {
               action: woosa.prefix+'_load_countries_items',
               security: Ajax.nonce,
               item_id,
               source,
               level,
            },
         });

      },

   };

   $( document ).ready( function() {
      countrySelection.init();
   });


})( jQuery, adn_country_selection );