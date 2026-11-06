import { getSetting } from '@woocommerce/settings';
import { dispatchAcceptData } from './accept';

export const getBlocksConfiguration = () => {
	const authnetServerData = getSetting( 'authnet_data', null );

	if ( ! authnetServerData ) {
		throw new Error( 'Authorize.net initialization data is not available' );
	}

	return authnetServerData;
};

export const getLoginID = () => {
	const loginID = getBlocksConfiguration()?.login_id;
	if ( ! loginID ) {
		throw new Error(
			'There is no Login ID available for Authorize.net. Make sure it is available on the wc.authnet_data.login_id property.'
		);
	}
	return loginID;
};

export const getClientKey = () => {
	return getBlocksConfiguration()?.client_key;
};

export const authnetDispatch = ( cardData ) =>
	dispatchAcceptData( {
		authData: {
			apiLoginID: getLoginID(),
			clientKey: getClientKey(),
		},
		cardData,
	} );
