/**
 * Accept.js helpers shared by the card and eCheck block payment methods.
 */

/**
 * Send card or bank data to Accept.js for a payment nonce.
 *
 * @param {Object} secureData { authData, cardData } or { authData, bankData }.
 * @return {Promise<Object>} The Ok response; rejects with the error response.
 */
export const dispatchAcceptData = ( secureData ) =>
	new Promise( ( resolve, reject ) => {
		if ( ! window.Accept?.dispatchData ) {
			reject( new Error( 'Accept.js is not loaded.' ) );
			return;
		}

		window.Accept.dispatchData( secureData, ( response ) => {
			if ( response?.messages?.resultCode === 'Ok' ) {
				resolve( response );
			} else {
				reject( response );
			}
		} );
	} );

/**
 * The text to show for an Accept.js error response, a thrown error or a message.
 *
 * @param {*}      error    Error response, Error or string.
 * @param {string} fallback Text when none can be found.
 * @return {string} Message.
 */
export const acceptErrorMessage = ( error, fallback ) => {
	const messages = error?.messages?.message;
	if ( Array.isArray( messages ) && messages.length && messages[ 0 ].text ) {
		return messages[ 0 ].text;
	}
	if ( error instanceof Error && error.message ) {
		return error.message;
	}
	if ( typeof error === 'string' && error ) {
		return error;
	}
	return fallback;
};
