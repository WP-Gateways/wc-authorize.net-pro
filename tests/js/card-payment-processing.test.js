import { renderHook } from '@testing-library/react';
import { setMockSetting, resetMockSettings } from '@woocommerce/settings';
import { usePaymentProcessing } from '../../src/payment-processing';

const emitResponse = {
	responseTypes: { SUCCESS: 'success', ERROR: 'error' },
	noticeContexts: { PAYMENTS: 'wc/payment-area' },
};
const billing = { billingAddress: { first_name: 'Jane', last_name: 'Doe', email: 'jane@example.com' } };

/**
 * Render the card hook and return the payment setup callback it registers.
 */
const paymentSetup = ( expiry = '12 / 30' ) => {
	let callback;
	const onPaymentSetup = ( cb ) => {
		callback = cb;
		return () => {};
	};
	renderHook( () =>
		usePaymentProcessing( billing, '4111 1111 1111 1111', expiry, '123', 'authnet', emitResponse, onPaymentSetup, () => () => {} )
	);
	return callback;
};

beforeEach( () => {
	resetMockSettings();
	setMockSetting( 'authnet_data', { login_id: 'login', client_key: 'client' } );
} );

afterEach( () => {
	delete window.Accept;
} );

describe( 'card payment setup', () => {
	it( 'sends the Accept.js nonce', async () => {
		window.Accept = {
			dispatchData: jest.fn( ( data, callback ) =>
				callback( { messages: { resultCode: 'Ok' }, opaqueData: { dataValue: 'nonce', dataDescriptor: 'COMMON.ACCEPT.INAPP.PAYMENT' } } )
			),
		};

		const result = await paymentSetup()();

		expect( result.type ).toBe( 'success' );
		expect( result.meta.paymentMethodData ).toMatchObject( {
			authnet_nonce: 'nonce',
			authnet_data_descriptor: 'COMMON.ACCEPT.INAPP.PAYMENT',
		} );
		expect( window.Accept.dispatchData.mock.calls[ 0 ][ 0 ].cardData ).toEqual( {
			cardNumber: '4111111111111111',
			cardCode: '123',
			month: '12',
			year: '30',
			fullName: 'Jane Doe',
		} );
	} );

	it( 'reports an Accept.js error with its text', async () => {
		window.Accept = {
			dispatchData: ( data, callback ) =>
				callback( { messages: { resultCode: 'Error', message: [ { code: 'E_WC_05', text: 'Please provide valid credit card number.' } ] } } ),
		};

		const result = await paymentSetup()();

		expect( result ).toEqual( { type: 'error', message: 'Please provide valid credit card number.' } );
	} );

	it( 'reports a thrown error as text, not an object', async () => {
		window.Accept = {
			dispatchData: () => {
				throw new Error( 'Accept.js failed to load.' );
			},
		};

		const result = await paymentSetup()();

		expect( result ).toEqual( { type: 'error', message: 'Accept.js failed to load.' } );
	} );
} );
