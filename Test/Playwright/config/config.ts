import coreConfig from '@loki-checkout/config';

const tokenCode: string = process.env.PAYNL_TOKENCODE || '';
const serviceId: string = process.env.PAYNL_SERVICEID || '';
const apiToken: string = process.env.PAYNL_API_TOKEN || '';

// Starting a PAY. transaction requires valid credentials
export const skipWithoutCredentials = (test): void => {
    test.skip(
        !tokenCode.trim() || !serviceId.trim() || !apiToken.trim(),
        'Missing PAY. credentials: PAYNL_TOKENCODE, PAYNL_SERVICEID and PAYNL_API_TOKEN'
    );
};

export default {
    ...coreConfig,
    modules: [
        'LokiCheckout_PayNl',
        'Paynl_Payment',
    ],
    secure_config: {
        'payment/paynl/apitoken_encrypted': apiToken,
    },
    config: {
        ...coreConfig.config,
        'payment/paynl/language': 'nl',
        'payment/paynl/testmode': '1',
        'payment/paynl/tokencode': tokenCode,
        'payment/paynl/serviceid': serviceId,
        'payment/paynl_payment_paylink/active': '1',
        'payment/paynl_payment_ideal/active': '1',
        'payment/paynl_payment_capayable_gespreid/active': '1',
        'payment/paynl_payment_billink/active': '1',
        'payment/paynl_payment_applepay/active': '1',
        'payment/paynl_payment_mistercash/active': '1',
        'payment/paynl_payment_googlepay/active': '1',
        'payment/paynl_payment_maestro/active': '1',
        'payment/paynl_payment_mastercard/active': '1',
        'payment/paynl_payment_paypal/active': '1',
        'payment/paynl_payment_visamastercard/active': '1',
    }
};
