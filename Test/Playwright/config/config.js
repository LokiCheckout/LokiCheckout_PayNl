export default {
  modules: [
    'Yireo_LokiCheckoutPayNl',
    'Paynl_Payment',
  ],
  profile: 'netherlands',
  config: {
    'payment/paynl/testmode': '1',
    'payment/paynl_payment_paylink/active': '1',
    'yireo_loki_checkout/general/theme': 'onestep',
    'currency/options/base': 'EUR',
  }
};
