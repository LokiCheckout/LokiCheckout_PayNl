---
status: complete
---

# Redirect to PAY. after placing the order

As a shopper, I want to be sent to the PAY. payment page after placing my order, so that I can finish paying.

## Acceptance criteria
- The redirect resolver `LokiCheckout\PayNl\Payment\Redirect\RedirectResolver` is registered as `pay_nl` in the frontend `LokiCheckout\Core\Payment\Redirect\RedirectResolverListing`.
- It returns `false` when the selected payment method is not a `Paynl\Payment\Model\Paymentmethod\Paymentmethod`.
- It returns `false` when the redirect context has no order.
- Otherwise it starts a PAY. transaction with `startTransaction($order)` and uses the result as the redirect URL.
- The integration test `RedirectResolverTest` checks that an empty redirect context gives an empty result.
- The Playwright test `payment-ideal-test` selects `paynl_payment_ideal`, places the order and lands on the `checkout.pay.nl` sandbox page showing "test betaling". The test is skipped when the `PAYNL_TOKENCODE`, `PAYNL_SERVICEID` or `PAYNL_API_TOKEN` credentials are missing.
