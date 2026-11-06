import { useEffect, useCallback, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { authnetDispatch, getClientKey } from './utils';
import { acceptErrorMessage } from './accept';

/**
 * Month and two-digit year from an expiry entered as "MM / YY" or "MM / YYYY".
 *
 * @param {string} expiryDate Expiry date.
 * @return {{month: string, year: string}} Expiry parts.
 */
const parseExpiry = ( expiryDate ) => {
	const [ month = '', year = '' ] = expiryDate.split( '/' ).map( ( part ) => part.trim() );
	return { month, year: year.slice( -2 ) };
};

export const usePaymentProcessing = (
	billing,
	cardNumber,
	expiryDate,
	cvc,
	PAYMENT_METHOD_NAME,
	emitResponse,
	onPaymentSetup,
	onCheckoutFail
) => {
	const [ error, setError ] = useState( '' );
	const onAuthnetError = useCallback( ( message ) => {
		setError( message );
		return message ? message : false;
	}, [] );

	// Hook into and register callbacks for events.
	useEffect( () => {
		const onSubmit = async () => {
			const billingAddress = billing.billingAddress;

			// Report a card field error first.
			if ( error ) {
				return {
					type: emitResponse.responseTypes.ERROR,
					message: error,
				};
			}

			try {
				let authnetArgs;

				if ( getClientKey() ) {
					const { month, year } = parseExpiry( expiryDate );
					const response = await authnetDispatch( {
						cardNumber: cardNumber.replace( /\s/g, '' ),
						cardCode: cvc,
						month,
						year,
						fullName: billingAddress?.first_name + ' ' + billingAddress?.last_name,
					} );

					authnetArgs = {
						authnet_nonce: response?.opaqueData?.dataValue,
						authnet_data_descriptor: response?.opaqueData?.dataDescriptor,
					};
				} else {
					authnetArgs = {
						'authnet-card-number': cardNumber,
						'authnet-card-expiry': expiryDate,
						'authnet-card-cvc': cvc,
					};
				}

				return {
					type: emitResponse.responseTypes.SUCCESS,
					meta: {
						paymentMethodData: {
							...authnetArgs,
							billing_email: billingAddress.email,
							billing_first_name: billingAddress?.first_name ?? '',
							billing_last_name: billingAddress?.last_name ?? '',
							paymentMethod: PAYMENT_METHOD_NAME,
							paymentRequestType: 'cc',
						},
						billingAddress,
					},
				};
			} catch ( e ) {
				return {
					type: emitResponse.responseTypes.ERROR,
					message: acceptErrorMessage( e, __( 'There was a problem with your card details. Please try again.', 'wc-authnet' ) ),
				};
			}
		};
		const unsubscribeProcessing = onPaymentSetup( onSubmit );
		return () => {
			unsubscribeProcessing();
		};
	}, [
		onPaymentSetup,
		billing.billingAddress,
		cardNumber,
		expiryDate,
		cvc,
		PAYMENT_METHOD_NAME,
		error,
		emitResponse.responseTypes.ERROR,
		emitResponse.responseTypes.SUCCESS,
	] );

	// hook into and register callbacks for events.
	useEffect( () => {
		const onError = ( { processingResponse } ) => {
			if ( processingResponse?.paymentDetails?.errorMessage ) {
				return {
					type: emitResponse.responseTypes.ERROR,
					message: processingResponse.paymentDetails.errorMessage,
					messageContext: emitResponse.noticeContexts.PAYMENTS,
				};
			}
			// so we don't break the observers.
			return true;
		};
		const unsubscribeAfterProcessing = onCheckoutFail( onError );
		return () => {
			unsubscribeAfterProcessing();
		};
	}, [
		onCheckoutFail,
		emitResponse.noticeContexts.PAYMENTS,
		emitResponse.responseTypes.ERROR,
	] );
	return onAuthnetError;
};
