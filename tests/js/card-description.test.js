import { render, screen } from '@testing-library/react';
import { setMockSetting } from '@woocommerce/settings';
import { registeredPaymentMethods } from '@woocommerce/blocks-registry';

// The card inputs aren't what these tests are about; the real library needs styles the test DOM lacks.
jest.mock( 'react-payment-inputs', () => ( {
	PaymentInputsWrapper: ( { children } ) => <div>{ children }</div>,
	usePaymentInputs: () => ( {
		wrapperProps: {},
		getCardImageProps: () => ( {} ),
		getCardNumberProps: () => ( {} ),
		getExpiryDateProps: () => ( {} ),
		getCVCProps: () => ( {} ),
		meta: {},
	} ),
} ) );
jest.mock( 'react-payment-inputs/images', () => ( {} ) );

/**
 * The card block shows the gateway description above the card fields, as classic checkout does, so the
 * test mode notice and the test card instructions show on the block checkout too.
 */
describe( 'card block description', () => {
	const settings = ( description ) =>
		setMockSetting( 'authnet_data', {
			title: 'Credit card (Authorize.net)',
			description,
			allowed_card_types: [ 'visa' ],
			supports: [ 'products' ],
		} );

	beforeAll( () => {
		settings( '' );
		// Each test file has its own module registry, so the script registers on first require.
		require( '../../src/index' );
	} );

	const renderCard = () => {
		const Content = registeredPaymentMethods.find( ( m ) => m.name === 'authnet' ).content.type;

		return render(
			<Content
				billing={ { billingAddress: {}, cartTotal: { value: 1000 }, currency: { code: 'USD', minorUnit: 2 } } }
				emitResponse={ { responseTypes: { SUCCESS: 'success', ERROR: 'error' }, noticeContexts: { PAYMENTS: 'wc/checkout/payments' } } }
				eventRegistration={ { onPaymentSetup: () => () => {}, onCheckoutFail: () => () => {} } }
				components={ { PaymentMethodIcons: () => null } }
			/>
		);
	};

	it( 'shows the description with the test mode notice above the card fields', () => {
		settings( '<p><strong>TEST MODE ENABLED</strong> Use 4111111111111111.</p>' );
		const { container } = renderCard();

		expect( screen.getByText( 'TEST MODE ENABLED' ).tagName ).toBe( 'STRONG' );
		expect( container.firstChild ).toHaveTextContent( 'TEST MODE ENABLED' );
	} );

	it( 'shows no description when there is none', () => {
		settings( '' );
		const { container } = renderCard();

		expect( container.querySelector( '.wc-block-authnet__description' ) ).toBeNull();
	} );
} );
