import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { __ } from '@wordpress/i18n';
import { PaymentInputsWrapper, usePaymentInputs } from 'react-payment-inputs';
import images from 'react-payment-inputs/images';
import { useState } from '@wordpress/element';
import { getBlocksConfiguration } from './utils';
import { usePaymentProcessing } from './payment-processing';
import './style.scss';

const PAYMENT_METHOD_NAME = 'authnet';

const getCreditCardIcons = () => {
	return Object.entries( getBlocksConfiguration()?.icons ?? {} ).map(
		( [ id, { src, alt } ] ) => {
			return {
				id,
				src,
				alt,
			};
		}
	);
};

const Label = ( props ) => {
	const { PaymentMethodLabel } = props.components;

	const labelText =
		getBlocksConfiguration()?.title ??
		__( 'Credit / Debit Card', 'wc-authnet' );

	return <PaymentMethodLabel text={ labelText } />;
};

const CreditCardComponent = ( {
								  billing,
								  eventRegistration,
								  emitResponse,
								  components
							  } ) => {
	const { onPaymentSetup, onCheckoutFail } = eventRegistration;
	const [ ccError, setCCError ] = useState(null);
	const [cardNumber, setCardNumber] = useState("");
	const [expiryDate, setExpiryDate] = useState("");
	const [cvc, setCVC] = useState("");
	const { PaymentMethodIcons } = components;

	const onAuthnetError = usePaymentProcessing(
		billing,
		cardNumber,
		expiryDate,
		cvc,
		PAYMENT_METHOD_NAME,
		emitResponse,
		onPaymentSetup,
		onCheckoutFail
	);

	const onCCError = ( error, erroredInputs ) => {
		setCCError(error);
		onAuthnetError(error);
		return true;
	};

	const cardNumberValidator = ({ cardNumber, cardType, errorMessages }) => {
		if(getBlocksConfiguration()?.allowed_card_types.indexOf(cardType.type) >= 0) {
			return;
		}
		if(cardType.type === 'dinersclub' && getBlocksConfiguration()?.allowed_card_types.indexOf('diners-club') >= 0) {
			return;
		}
		return getBlocksConfiguration()?.card_disallowed_error;
	}

	const ERROR_MESSAGES = {
		emptyCardNumber: getBlocksConfiguration()?.no_card_number_error,
		invalidCardNumber: getBlocksConfiguration()?.card_number_error,
		emptyExpiryDate: getBlocksConfiguration()?.no_card_expiry_error,
		monthOutOfRange: getBlocksConfiguration()?.card_expiry_error,
		yearOutOfRange: getBlocksConfiguration()?.card_expiry_error,
		dateOutOfRange: getBlocksConfiguration()?.card_expiry_error,
		invalidExpiryDate: getBlocksConfiguration()?.card_expiry_error,
		emptyCVC: getBlocksConfiguration()?.no_cvv_error,
		invalidCVC: getBlocksConfiguration()?.card_cvc_error
	};

	const {
		wrapperProps,
		getCardImageProps,
		getCardNumberProps,
		getExpiryDateProps,
		getCVCProps,
		meta
	} = usePaymentInputs({ cardNumberValidator, onError: onCCError, errorMessages: ERROR_MESSAGES });

	const cardIcons = getCreditCardIcons();

	const renderedCardElement = (
		<div className="wc-block-gateway-container wc-inline-card-element wc-block-authnet-gateway-container">
			<PaymentInputsWrapper {...wrapperProps}>
				<svg {...getCardImageProps({ images })} />
				<input {...getCardNumberProps({ onChange: e => setCardNumber(e.target.value) })} />
				<input {...getExpiryDateProps({ onChange: e => setExpiryDate(e.target.value) })} />
				<input {...getCVCProps({ onChange: e => setCVC(e.target.value) })} />
			</PaymentInputsWrapper>
		</div>
	)
	return (
		<>
			{ renderedCardElement }
			{ PaymentMethodIcons && cardIcons.length > 0 && (
				<PaymentMethodIcons icons={ cardIcons } align="left" />
			) }
		</>
	);
};

const authnetCcPaymentMethod = {
	name: PAYMENT_METHOD_NAME,
	label: <Label />,
	content: <CreditCardComponent />,
	edit: <CreditCardComponent />,
	canMakePayment: () => true,
	ariaLabel: __(
		'Authnet payment method',
		'wc-authnet'
	),
	supports: {
		// Use `false` as fallback values in case server provided configuration is missing.
		showSavedCards: getBlocksConfiguration()?.showSavedCards ?? false,
		showSaveOption: getBlocksConfiguration()?.showSaveOption ?? false,
		features: getBlocksConfiguration()?.supports ?? [],
	},
};
registerPaymentMethod( authnetCcPaymentMethod );
