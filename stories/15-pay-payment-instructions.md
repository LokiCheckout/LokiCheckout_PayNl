---
status: complete
---

# Show PAY. payment instructions

As a shopper, I want to see the instructions that the store owner wrote for a PAY. method, so that I know how the payment works.

## Acceptance criteria
- The plugin `LokiCheckout\PayNl\Plugin\AddPaymentInstructionsPlugin` returns `payment/<code>/instructions` from `PaymentMethodsViewModel::getPaymentInstructions()` for `paynl_*` methods.
- Instructions from other sources are kept. Other methods are not touched.
- The integration test `AddPaymentInstructionsPluginTest` covers this.
