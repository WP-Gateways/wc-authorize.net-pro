/**
 * Stand-in for @woocommerce/blocks-registry: records the payment methods a script registers.
 */
export const registeredPaymentMethods = [];

export const registerPaymentMethod = ( config ) => {
	registeredPaymentMethods.push( config );
};
