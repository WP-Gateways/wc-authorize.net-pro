import { acceptErrorMessage, dispatchAcceptData } from '../../src/accept';

const acceptError = ( ...texts ) => ( {
	messages: {
		resultCode: 'Error',
		message: texts.map( ( text, i ) => ( { code: `E_WC_0${ i }`, text } ) ),
	},
} );

describe( 'acceptErrorMessage', () => {
	it( 'uses the first message of an Accept.js error response', () => {
		expect( acceptErrorMessage( acceptError( 'Invalid card number.', 'Second' ), 'Fallback' ) ).toBe( 'Invalid card number.' );
	} );

	it( 'uses the message of a thrown Error', () => {
		expect( acceptErrorMessage( new Error( 'Accept.js is not loaded.' ), 'Fallback' ) ).toBe( 'Accept.js is not loaded.' );
	} );

	it( 'uses a string as it is', () => {
		expect( acceptErrorMessage( 'Card Type Not Accepted.', 'Fallback' ) ).toBe( 'Card Type Not Accepted.' );
	} );

	it( 'falls back for anything else', () => {
		expect( acceptErrorMessage( { unexpected: true }, 'Fallback' ) ).toBe( 'Fallback' );
		expect( acceptErrorMessage( undefined, 'Fallback' ) ).toBe( 'Fallback' );
	} );
} );

describe( 'dispatchAcceptData', () => {
	afterEach( () => {
		delete window.Accept;
	} );

	it( 'resolves with an Ok response', async () => {
		const response = { messages: { resultCode: 'Ok' }, opaqueData: { dataValue: 'nonce' } };
		window.Accept = { dispatchData: jest.fn( ( data, callback ) => callback( response ) ) };

		await expect( dispatchAcceptData( { bankData: {} } ) ).resolves.toBe( response );
		expect( window.Accept.dispatchData ).toHaveBeenCalledWith( { bankData: {} }, expect.any( Function ) );
	} );

	it( 'rejects with an error response', async () => {
		const response = acceptError( 'Invalid routing number.' );
		window.Accept = { dispatchData: ( data, callback ) => callback( response ) };

		await expect( dispatchAcceptData( {} ) ).rejects.toBe( response );
	} );

	it( 'rejects when Accept.js is not loaded', async () => {
		await expect( dispatchAcceptData( {} ) ).rejects.toThrow( 'Accept.js' );
	} );
} );
