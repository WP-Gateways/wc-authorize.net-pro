// listen for any errors on the form & send the information to a function to handle them
jQuery("body").on( "wc-authnet-error", {}, function( event, response ) {
    var fn = window[ wc_authnet_params.error_response_handler ];
	// make sure the function exists
    if ( typeof fn === 'function' ) {
        fn.apply( null, [ response ] );
    }
} );

// display error & unblock the form
function authnetErrorHandler( responseObject ) {
    jQuery('.woocommerce_error, .woocommerce-error, .woocommerce-message, .woocommerce_message, .authnet_token').remove();
    jQuery('#authnet-card-number').closest('p').before( '<ul class="woocommerce_error woocommerce-error"><li>' + responseObject.response.error.message + '</li></ul>' );
    responseObject.form.unblock();
}
