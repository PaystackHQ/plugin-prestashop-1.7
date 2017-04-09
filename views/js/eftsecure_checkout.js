jQuery( function( $ ) {
	'use strict';
	var mg_paystack_form = {

		init: function( form ) {
			eftSec.checkout.settings.serviceUrl = "{protocol}://paystack.callpay.com/eft";
			this.form          = form;
			this.paystack_submit = false;

			$( this.form )
				.on( 'click', '#place_order', this.onSubmit );
		},

		isPaystackChosen: function() {
			//return $( '#payment_method_paystack' ).is( ':checked' );
			return true;
		},

		isPaystackModalNeeded: function( e ) {
			// Don't affect submit if modal is not needed.
			if (!mg_paystack_form.isPaystackChosen() ) {
				//return false;
			}
            // Don't affect submit if payment already complete.
			if (mg_paystack_form.paystack_submit) {
				//return false;
			}
			return true;
		},

		block: function() {
			mg_paystack_form.form.block({
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			});
		},

		unblock: function() {
			mg_paystack_form.form.unblock();
		},

		onClose: function() {
			mg_paystack_form.unblock();
		},

		onSubmit: function( e ) {
			if ( mg_paystack_form.isPaystackModalNeeded()) {
				//var $data = jQuery('#paystack-payment-data');
				e.preventDefault();

				mg_paystack_form.block();
				eftSec.checkout.init({
					organisation_id: mg_paystack_params.organisation_id,
					token: mg_paystack_params.token,
					reference: mg_paystack_params.reference,
					primaryColor: mg_paystack_params.pcolor,
					secondaryColor: mg_paystack_params.scolor,
					amount: mg_paystack_params.amount,
                    onLoad: function() {
						mg_paystack_form.unblock();
					},
                    onComplete: function(data) {
                        eftSec.checkout.hideFrame();
                        console.log('Transaction Completed');
                        mg_paystack_form.paystack_submit = true;
                        var $form = mg_paystack_form.form;
                        if ($form.find( 'input.paystack_transaction_id' ).length > 0) {
                            $form.find('input.paystack_transaction_id').remove();
                        }
                        $form.append( '<input type="hidden" class="paystack_transaction_id" name="paystack_transaction_id" value="' + data.transaction_id + '"/>' );
                        $form.submit();
                    }
				});

				return false;
			}

			return true;
		},

		resetModal: function() {
			if (mg_paystack_form.form.find( 'input.paystack_transaction_id' ).length > 0) {
                mg_paystack_form.form.find('input.paystack_transaction_id').remove();
            }
			mg_paystack_form.paystack_submit = false;
		}
	};

	mg_paystack_form.init( $( "form#add_payment" ) );
} );

$(document).ready(function(){
	$('#place_order').trigger('click');
});