( function($, woosa){

   if ( ! woosa ) {
      return;
   }

   var Ajax = woosa.ajax;
   var Translation = woosa.translation;

   var moduleCategorySelection = {

      init: function(){

         this.load_items();
      },

      load_items: function(){

         let target = 'data-'+woosa.prefix+'-cs-load-items';

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
               action: woosa.prefix+'_cs_load_items',
               security: Ajax.nonce,
               item_id,
               source,
               level,
            },
         });

      },

   };

   $( document ).ready( function() {
      moduleCategorySelection.init();
   });


})( jQuery, adn_module_category_selection );