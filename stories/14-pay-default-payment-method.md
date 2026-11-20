---
status: complete
---

# Preselect the PAY. default payment method

As a store owner, I want the PAY. "default payment method" setting to preselect that method in the Loki Checkout.

## Acceptance criteria
- When `payment/paynl/default_payment_option` is set and that method is available, `PaymentMethodsRepository::getDefaultPayment()` returns it.
- This also works when the Loki setting to select a default payment method is off.
- When the configured method is not available, the Loki default is kept.
- The integration tests `DefaultPaymentMethodTest` and `PaymentMethodsRepositoryPluginTest` and the Playwright test `default-method-test` cover this.
