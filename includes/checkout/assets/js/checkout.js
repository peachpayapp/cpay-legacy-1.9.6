( function($, woosa){

   if ( ! woosa ) {
      return;
   }

   var Ajax = woosa.ajax;
   var Translation = woosa.translation;
   var Prefix = woosa.prefix;
   var Util = woosa.util;

   var processCheckout = {

      /**
       * Initiates this module.
       */
      init: function(){

         //stop here if Adyen lib is not loaded
         if(typeof AdyenCheckout == 'undefined') return;

         this.init_popup();

         this.generate_web_component();
         this.regenerate_web_component();

         this.display_payment_action();

      },


      /**
       * Init popup used for payment actions
       */
      init_popup: function(){

         $('.'+Prefix+'-popup').each(function(){
            let _this = $(this),
               blur = (typeof _this.attr('data-blur') == 'undefined') ? false : true,
               escape = (typeof _this.attr('data-escape') == 'undefined') ? false : true;

            _this.popup({
               autoopen: true,
               blur: blur,
               escape: escape,
               scrolllock: true,
               autozindex: true,
               onopen: function() {
                  // Close all other open popups
                  $('.'+Prefix+'-popup').not(_this).popup('hide');
               }
            });
         });
      },


      /**
       * Init Adyen checkout.
       *
       * @returns {object}
       */
      AdyenCheckout: async function(){

         return await AdyenCheckout({
            paymentMethodsResponse: woosa.api.response_payment_methods,
            clientKey: woosa.api.origin_key,
            locale: woosa.locale,
            environment: woosa.api.environment,
            onChange: function (state, component) {

               if(state.data.paymentMethod.type == 'scheme'){

                  let methodType = 'bcmc' === component.props.type ? 'bcmc' : state.data.paymentMethod.type;

                  processCheckout.setEncryptedCardData(state, component, methodType);

               }else if($.inArray(state.data.paymentMethod.type, ['ideal', 'dotpay', 'molpay_ebanking_fpx_MY', 'molpay_ebanking_TH'] ) != -1){

                  processCheckout.setBankIssuerData(state, component);

               }else if(state.data.paymentMethod.type == 'blik'){

                  processCheckout.setBlikData(state, component);

               }

            },
            onAdditionalDetails: function (state, component) {

               let elem = $(component._node),
                  order_id = elem.attr('data-order_id');

               jQuery('.'+Prefix+'-component__text').show();

               jQuery.ajax({
                  url   : Ajax.url,
                  method: 'POST',
                  data: {
                     action     : Prefix+'_additional_details',
                     security   : Ajax.nonce,
                     state_data: state.data,
                     order_id   : order_id
                  },
                  success: function(res) {
                     if(res.data.redirect){
                        window.location.href = res.data.redirect;
                     }
                  }
               });

            },
            onError: function(err){
               console.log(err)
            }
         });
      },


      /**
       * Generates the Web Component of the payment method.
       */
      generate_web_component: function(){

         if($('.'+Prefix+'-datepicker').length > 0){
            $('.'+Prefix+'-datepicker').datepicker({
               dateFormat : "dd-mm-yy",
               changeYear: true,
               changeMonth: true,
            })
         }

         this.generateCardForm();
         this.generateNewCardForm();
         this.generateGooglePay();
         this.generateApplePay();
         this.generateBankIssuer();

      },


      /**
       * Re-generates the Web Component of the payment method after WC checkout is updated.
       */
      regenerate_web_component: function(){

         $(document).on('updated_checkout', this, function(e){

            e.data.generate_web_component();

         });

      },


      /**
       * Displays the payment action based on the received action data
       * @since 1.3.0
       */
      display_payment_action: function(){

         var elem = '#'+Prefix+'-payment-action-data';

         if($(elem).length > 0){

            // remove duplicates
            if ($('.'+Prefix+'-component[data-payment_action]').length > 1) {

               var lastElem;
               $($('.'+Prefix+'-component[data-payment_action]')).each(function() {
                  lastElem = $(this);
               });

               $($('.'+Prefix+'-component[data-payment_action]')).each(function() {
                  if (this !== lastElem.get(0)) {
                     $(this).remove();
                  }
               });
            }

            var action = JSON.parse($(elem).attr('data-payment_action'));

            processCheckout.AdyenCheckout().then(function(response){
               response.createFromAction(action).mount(elem);

               if(action.type !== "redirect") {
                  jQuery('.'+Prefix+'-component__text').hide();
               }
            });
         }
      },



      /**
       * Clear card data from the hidden fileds.
       *
       * @param {string} methodType
       */
       clearCardForm: function(methodType){

         jQuery('#'+Prefix+'-'+methodType+'-card-number').val('');
         jQuery('#'+Prefix+'-'+methodType+'-card-brand').val('');
         jQuery('#'+Prefix+'-'+methodType+'-card-exp-month').val('');
         jQuery('#'+Prefix+'-'+methodType+'-card-exp-year').val('');
         jQuery('#'+Prefix+'-'+methodType+'-card-cvc').val('');
         jQuery('#'+Prefix+'-'+methodType+'-card-holder').val('');
         jQuery('#'+Prefix+'-'+methodType+'-sci').val('');
         jQuery('#'+Prefix+'-'+methodType+'-store-card').val('');

         Util.debugLog('Clear the encrypted card data.');

      },


      /**
       * Fills the encrypted card data in the hidden fields.
       *
       * @param {object} state
       * @param {object} component
       * @param {string} methodType
       */
      setEncryptedCardData: function(state, component = {}, methodType = ''){

         if(state && state.isValid){

            var store_card = state.data.storePaymentMethod ? state.data.storePaymentMethod : '0';

            if(component && component._node){

               jQuery('#'+component._node.id).data('card_state', state);

               Util.debugLog('Saved temporarily the encrypted card data on the element.');

            }

            jQuery('#'+Prefix+'-'+methodType+'-card-number').val(state.data.paymentMethod.encryptedCardNumber);
            jQuery('#'+Prefix+'-'+methodType+'-card-brand').val(state.data.paymentMethod.brand);
            jQuery('#'+Prefix+'-'+methodType+'-card-exp-month').val(state.data.paymentMethod.encryptedExpiryMonth);
            jQuery('#'+Prefix+'-'+methodType+'-card-exp-year').val(state.data.paymentMethod.encryptedExpiryYear);
            jQuery('#'+Prefix+'-'+methodType+'-card-cvc').val(state.data.paymentMethod.encryptedSecurityCode);
            jQuery('#'+Prefix+'-'+methodType+'-card-holder').val(state.data.paymentMethod.holderName);
            jQuery('#'+Prefix+'-'+methodType+'-sci').val(state.data.paymentMethod.storedPaymentMethodId);
            jQuery('#'+Prefix+'-'+methodType+'-store-card').val(store_card);

            if(state.data.installments){
               jQuery('#'+Prefix+'-'+methodType+'-card-installments').val(state.data.installments.value);
            }

            Util.debugLog('Set the encrypted card data.');

         }else{

            this.clearCardForm(methodType);
         }
      },


      /**
       * Fills the selected bank issuer in the hidden field.
       *
       * @param {object} state
       * @param {object} component
       */
      setBankIssuerData: function(state, component){

         var value = '';

         if(state && state.isValid){
            value = state.data.paymentMethod.issuer;
         }

         jQuery('#'+Prefix+'_'+state.data.paymentMethod.type+'_issuer').val(value);

      },


      setBlikData: function(state, component){

         var value = '';

         if(state && state.isValid){
            value = state.data.paymentMethod.blikCode;
         }

         jQuery('#'+Prefix+'_'+state.data.paymentMethod.type+'_code').val(value);
      },


      /**
       * Generates credidcard component.
       */
      generateCardForm: function(){

         jQuery('[data-'+Prefix+'-stored-card]').off('click').on('click', function(){

            var _this            = jQuery(this),
               parent            = _this.parent(),
               current           = parent.find('.'+Prefix+'-stored-card__fields'),
               methodType        = _this.attr('data-'+Prefix+'-stored-card-type'),
               formElemId        = _this.attr('data-'+Prefix+'-stored-card'),
               card_installments = jQuery('[data-'+Prefix+'-card-installments]').val(),
               formElem          = jQuery('#'+formElemId),
               formType          = 'scheme' === methodType ? 'card' : methodType,
               cardState         = formElem.data('card_state'),
               methodIndex       = formElemId.replace(/[^0-9\.]/g, '');

            processCheckout.AdyenCheckout().then(function(response){

               paymentMethodsConfiguration = '';
               storedMethods = response.paymentMethodsResponse.storedPaymentMethods;

               if(formElem.length > 0){

                  //new card
                  if( '' === methodIndex){

                     if( '' == formElem.children(0).html() && formElem.children(0).children(0).length === 0 ){

                        if(Util.isJson(card_installments)){

                           card_installments = JSON.parse(card_installments);

                           if(card_installments.constructor === Array){

                              paymentMethodsConfiguration = {
                                 card: {
                                    installmentOptions: {
                                       card: {
                                          values: card_installments,
                                          // Shows regular and revolving as plans shoppers can choose.
                                          // plans: [ 'regular', 'revolving' ]
                                       },
                                    },
                                    // Shows payment amount per installment.
                                    showInstallmentAmounts: true
                                 }
                              }
                           }
                        }

                        response.setOptions({
                           paymentMethodsConfiguration: paymentMethodsConfiguration,
                        }).create(formType, {
                           locale: woosa.locale,
                           brands: woosa.api.card_types,
                           enableStoreDetails: woosa.api.store_card,
                           hasHolderName: woosa.api.has_holder_name,
                           holderNameRequired: woosa.api.holder_name_required,
                        }).mount('#'+formElemId);

                        Util.debugLog('Initiated the form for using a new card.');

                     }else{

                        processCheckout.setEncryptedCardData(cardState, {}, methodType);
                     }

                     jQuery('#'+Prefix+'-'+methodType+'-is-stored-card').val('no');

                  //stored card
                  }else{

                     if('' == formElem.html()){

                        response.create(formType, storedMethods[methodIndex]).mount('#'+formElemId);

                        Util.debugLog('Initiated the form for the existing card.');

                     }else{

                        processCheckout.setEncryptedCardData(cardState, {}, methodType);
                     }

                     jQuery('#'+Prefix+'-'+methodType+'-is-stored-card').val('yes');
                  }
               }

               jQuery('.'+Prefix+'-stored-card__fields').closest('.'+Prefix+'-stored-card').not(parent).removeClass('selected');
               jQuery('.'+Prefix+'-stored-card__fields').not(current).slideUp();

               current.slideToggle();
               parent.addClass('selected');
            });
         });
      },


      /**
       * Generates the new card form if no saved card are present
       */
      generateNewCardForm:function () {

         $( document.body ).on( 'updated_checkout', function () {

            jQuery('.wc_payment_method').each(function(index, item){

               var method = jQuery(item),
                  payment_box = method.find('.payment_box');

               //only for visible
               if(payment_box.css('display') == 'block'){

                  var has_stored_cards = payment_box.find('.'+Prefix+'-stored-cards > .is-stored-card').length > 0,
                     card = payment_box.find('[data-'+Prefix+'-stored-card]');

                  if(has_stored_cards){
                     return;
                  }

                  var parent              = card.parent(),
                     current              = parent.find('.'+Prefix+'-stored-card__fields'),
                     methodType           = card.attr('data-'+Prefix+'-stored-card-type'),
                     formElemId           = card.attr('data-'+Prefix+'-stored-card'),
                     formElem             = jQuery('#'+formElemId),
                     formType             = 'scheme' === methodType ? 'card' : methodType,
                     card_installments    = jQuery('[data-'+Prefix+'-card-installments]').val(),
                     paymentMethodsConfig = '';

                  if(formElem.length > 0) {

                     //new card
                     if (!!formElemId && '' === formElemId.replace(/[^0-9\.]/g, '')) {

                        if ('' == formElem.children(0).html() && formElem.children(0).children(0).length === 0) {

                           if (Util.isJson(card_installments)) {

                              card_installments = JSON.parse(card_installments);

                              if (card_installments.constructor === Array) {

                                    paymentMethodsConfig = {
                                       card: {
                                          installmentOptions: {
                                                card: {
                                                   values: card_installments,
                                                   // Shows regular and revolving as plans shoppers can choose.
                                                   // plans: [ 'regular', 'revolving' ]
                                                },
                                          },
                                          // Shows payment amount per installment.
                                          showInstallmentAmounts: true
                                       }
                                    }
                              }
                           }

                           processCheckout.AdyenCheckout().then(function(response){

                              response.setOptions({
                                 paymentMethodsConfiguration: paymentMethodsConfig,
                              }).create(formType, {
                                 locale: woosa.locale,
                                 brands: woosa.api.card_types,
                                 enableStoreDetails: woosa.api.store_card,
                                 hasHolderName: woosa.api.has_holder_name,
                                 holderNameRequired: woosa.api.holder_name_required,
                              }).mount('#' + formElemId);

                              Util.debugLog('Initiated the form without saved cards.');

                              jQuery('#'+Prefix+'-'+methodType+'-is-stored-card').val('no');

                              jQuery('.'+Prefix+'-stored-card__fields').closest('.'+Prefix+'-stored-card').not(parent).removeClass('selected');
                              jQuery('.'+Prefix+'-stored-card__fields').not(current).slideUp();

                              current.slideDown();
                              parent.addClass('selected');
                           });
                        }
                     }
                  }
               }

            });

         });


      },


      /**
       * Generates GooglePay component.
       */
      generateGooglePay: function(){

         if(jQuery('#woosa_adyen_googlepay_button').length > 0){

            const test_mode = 'yes' !== jQuery('#woosa_adyen_googlepay_testmode').val() && 'test' !== woosa.api.environment ? false : true ,
               merchant_id = jQuery('#woosa_adyen_googlepay_merchant_identifier').val();

            // Get current cart total dynamically
            var total = processCheckout.getCurrentCartTotal();

            processCheckout.AdyenCheckout().then(function(response){

               var component = response.create(adn_checkout.google_method_type, {
                  countryCode: woosa.cart.country,
                  environment: test_mode ? 'TEST' : 'PRODUCTION',
                  amount: {
                     currency: woosa.currency,
                     value: total, // Already in cents from getCurrentCartTotal()
                  },
                  configuration: {
                     gatewayMerchantId: woosa.api.adyen_merchant,
                     merchantName: woosa.site_name,
                     merchantId: merchant_id
                  },
                  buttonColor: "default",
                  onAuthorized: (data) => {

                     // Get the most current cart total right before authorization
                     var currentTotal = processCheckout.getCurrentCartTotal();
                     
                     // Update the component amount if it has changed
                     if(component.props && component.props.amount && component.props.amount.value !== currentTotal){
                        component.props.amount.value = currentTotal;
                        Util.debugLog('Google Pay amount updated to current cart total: ' + currentTotal);
                     }

                     jQuery('#'+Prefix+'-googlepay-container .googlepay-description').html(data.paymentMethodData.description).show();
                     jQuery('#woosa_adyen_googlepay_description').val(data.paymentMethodData.description);
                     jQuery('#woosa_adyen_googlepay_token').val(data.paymentMethodData.tokenizationData.token);
                  }
               });

               component.isAvailable().then(() => {

                  component.mount("#woosa_adyen_googlepay_button");

               }).catch(e => {
                  console.log(e);
                  jQuery('.wc_payment_method .payment_method_woosa_adyen_googlepay').remove();
               });

            });
         }
      },


      /**
       * Gets the current cart total from the checkout form
       * @returns {number} Current cart total in cents
       */
      getCurrentCartTotal: function(){
         // Try to get the total from the order review section first
         var orderTotal = jQuery('.woocommerce-checkout-review-order-table .order-total .amount').text();
         
         if(orderTotal){
            // Remove currency symbols and convert to number
            var cleanTotal = orderTotal.replace(/[^\d.,]/g, '').replace(',', '.');
            var total = parseFloat(cleanTotal);
            
            if(!isNaN(total)){
               return Math.round(total * 100); // Convert to cents
            }
         }
         
         // Fallback to the original woosa.cart.total if we can't get the current total
         return Math.round((woosa.cart.total) * 100);
      },

      /**
       * Generates AppleyPay component.
       */
      generateApplePay: function(){

         if(jQuery('#applepay-container').length > 0){

            // Get current cart total dynamically
            var total = processCheckout.getCurrentCartTotal();

            processCheckout.AdyenCheckout().then(function(response){

               var component = response.create("applepay", {
                  amount: {
                     currency: woosa.currency,
                     value: total.toString(), // Convert to string for Adyen
                  },
                  countryCode: woosa.cart.country,
                  onAuthorized: (callBackSuccess,callBackError,ApplePayPaymentAutorizedEvent) => {

                     // Get the most current cart total right before authorization
                     var currentTotal = processCheckout.getCurrentCartTotal();
                     
                     // Update the component amount if it has changed
                     if(component.props && component.props.amount && component.props.amount.value !== currentTotal.toString()){
                        component.props.amount.value = currentTotal.toString();
                        Util.debugLog('Apple Pay amount updated to current cart total: ' + currentTotal);
                     }

                     if( ! ApplePayPaymentAutorizedEvent.payment.token.paymentData){
                        callBackError()
                     }else{

                        var token = JSON.stringify(ApplePayPaymentAutorizedEvent.payment.token.paymentData);

                        jQuery('#woosa_adyen_applepay_token').val(token);

                        callBackSuccess();

                        // Auto-submit the checkout form after successful Apple Pay authorization
                        processCheckout.autoSubmitApplePayCheckout();

                        document.dispatchEvent( new CustomEvent('WoosaApplePayPaymentAutorizedEvent', {'detail': {ApplePayPaymentAutorizedEvent : ApplePayPaymentAutorizedEvent} }));
                     }
                  }
               });

               component.isAvailable().then(() => {

                  component.mount("#applepay-container");

               }).catch(e => {
                  console.log(e)
                  jQuery('.payment_method_woosa_adyen_applepay').remove();
               });
            });
         }
      },

      /**
       * Automatically submits the checkout form for Apple Pay payments
       */
      autoSubmitApplePayCheckout: function() {
         
         // Check if Apple Pay is actually selected
         if (!jQuery('input[name="payment_method"][value="woosa_adyen_applepay"]').is(':checked')) {
            return;
         }
         
         // Add visual feedback for processing state
         jQuery('.payment_method_woosa_adyen_applepay').addClass('processing');
         jQuery('#applepay-container').addClass('processing');
         
         // Select Apple Pay payment method (in case it wasn't already selected)
         jQuery('input[name="payment_method"][value="woosa_adyen_applepay"]').prop('checked', true).trigger('change');
         
         // Trigger WooCommerce checkout update to ensure form is ready
         jQuery(document.body).trigger('update_checkout');
         
         // Small delay to ensure the form is updated before submission
         setTimeout(function() {
            
            // Check if form is valid before submitting
            if (jQuery('#place_order').length > 0 && !jQuery('#place_order').is(':disabled')) {
               
               // Show loading state
               jQuery('#place_order').addClass('loading').prop('disabled', true);
               
               // Submit the checkout form
               jQuery('form.checkout').submit();
               
               Util.debugLog('Auto-submitting checkout form for Apple Pay payment');
               
               // Set a timeout to remove processing state if submission takes too long
               setTimeout(function() {
                  if (jQuery('.payment_method_woosa_adyen_applepay').hasClass('processing')) {
                     jQuery('.payment_method_woosa_adyen_applepay').removeClass('processing');
                     jQuery('#applepay-container').removeClass('processing');
                     jQuery('#place_order').removeClass('loading').prop('disabled', false);
                     
                     // Show fallback message
                     if (jQuery('.woocommerce-error').length === 0) {
                        jQuery('<div class="woocommerce-message woocommerce-message--info" style="background-color: #d1ecf1; border-color: #bee5eb; color: #0c5460; padding: 12px; margin: 10px 0; border-radius: 4px;">' +
                              '<strong>Apple Pay authorized!</strong> Please click "Place Order" to complete your purchase.' +
                              '</div>').insertBefore('#place_order');
                     }
                  }
               }, 5000); // 5 second timeout
               
            } else {
               console.log('Checkout form not ready for submission');
               // Remove processing state if submission fails
               jQuery('.payment_method_woosa_adyen_applepay').removeClass('processing');
               jQuery('#applepay-container').removeClass('processing');
               
               // Show fallback message
               jQuery('<div class="woocommerce-message woocommerce-message--info" style="background-color: #d1ecf1; border-color: #bee5eb; color: #0c5460; padding: 12px; margin: 10px 0; border-radius: 4px;">' +
                     '<strong>Apple Pay authorized!</strong> Please click "Place Order" to complete your purchase.' +
                     '</div>').insertBefore('#place_order');
            }
            
         }, 1000); // 1 second delay to ensure form is ready
      },


      /**
       * Generates bank issuer component.
       */
      generateBankIssuer: function(){

         var items = ['ideal', 'blik', 'molpay_ebanking_fpx_MY', 'molpay_ebanking_TH', 'onlineBanking_PL'];

         items.map(function(item){

            var elem = '#'+Prefix+'-'+item+'-container';

            if($(elem).length > 0){

               processCheckout.AdyenCheckout().then(function(response){
                  response.create(item).mount(elem);
               });

            }
         });
      },
   };

   $( document ).ready( function() {
      processCheckout.init();
      
      // Clean up Apple Pay processing state when page unloads (successful submission)
      $(window).on('beforeunload', function() {
         if (jQuery('.payment_method_woosa_adyen_applepay').hasClass('processing')) {
            jQuery('.payment_method_woosa_adyen_applepay').removeClass('processing');
            jQuery('#applepay-container').removeClass('processing');
         }
      });
   });

})( jQuery, adn_util );
