import { setMockSetting } from '@woocommerce/settings';
import { registeredPaymentMethods } from '@woocommerce/blocks-registry';

describe( 'card block registration', () => {
	it( 'registers the authnet payment method with the server settings', () => {
		setMockSetting( 'authnet_data', {
			title: 'Credit card (Authorize.net)',
			supports: [ 'products', 'tokenization' ],
			showSavedCards: true,
			showSaveOption: false,
		} );

		// Each test file has its own module registry, so the script registers on first require.
		require( '../../src/index' );

		const method = registeredPaymentMethods.find( ( m ) => m.name === 'authnet' );
		expect( method ).toBeDefined();
		expect( method.supports ).toEqual( {
			showSavedCards: true,
			showSaveOption: false,
			features: [ 'products', 'tokenization' ],
		} );
		expect( method.canMakePayment() ).toBe( true );
	} );
} );
