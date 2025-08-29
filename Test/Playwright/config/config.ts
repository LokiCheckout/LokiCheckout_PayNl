import coreConfig from '@loki/config';

export default {
    ...coreConfig,
    modules: [
        'LokiCheckout_PayNl',
        'Paynl_Payment',
    ],
    config: {
        ...coreConfig.config,
        'payment/paynl/testmode': '1',
        'payment/paynl_payment_paylink/active': '1',
    }
};
