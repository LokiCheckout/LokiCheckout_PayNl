import coreConfig from '@loki/config';

export default {
    ...coreConfig,
    modules: [
        'LokiCheckout_PayNl',
        'Paynl_Payment',
    ],
    secure_config: {
        'payment/paynl/apitoken_encrypted': process.env.PAYNL_API_TOKEN || '',
    },
    config: {
        ...coreConfig.config,
        'payment/paynl/language': 'nl',
        'payment/paynl/testmode': '1',
        'payment/paynl/tokencode': process.env.PAYNL_TOKENCODE || '',
        'payment/paynl/serviceid': process.env.PAYNL_SERVICEID || '',
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
