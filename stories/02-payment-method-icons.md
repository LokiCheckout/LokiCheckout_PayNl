---
status: complete
---

# Show PAY. icons for payment methods

As a shopper, I want to see the logo of each PAY. payment method in the checkout, so that I can recognise the payment method quickly.

## Acceptance criteria
- The icon resolver `LokiCheckout\PayNl\Payment\Icon\IconResolver` is registered as `pay_nl` in the frontend `LokiCheckout\Core\Payment\Icon\IconResolverListing`.
- It only handles payment method codes that match `^paynl_(.*)$`. For any other code it returns `false`.
- It returns `false` when the `Paynl_Payment` module is disabled.
- The icon URL comes from `Paynl\Payment\Model\Config::getIconUrl()` for the payment method code.
- The rendered icon uses the payment method code as its `alt` attribute.
- The integration test `IconResolverTest` checks that `paynl_payment_ideal` produces an `<img src=` tag.
