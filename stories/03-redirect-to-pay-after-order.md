---
status: complete
---

# Redirect to PAY. after placing the order

As a shopper, I want to be sent to the PAY. payment page after placing my order, so that I can finish paying.

## Acceptance criteria
- The redirect resolver `LokiCheckout\PayNl\Payment\Redirect\RedirectResolver` is registered in the frontend `LokiCheckout\Core\Payment\Redirect\RedirectResolverListing`.
- It returns `false` when the selected payment method is not a `Paynl\Payment\Model\Paymentmethod\PaymentMethod`.
- It returns `false` when the redirect context has no order.
- Otherwise it redirects to the PAY. controller `paynl/checkout/redirect` with the query parameters `nocache` and `mqid` (masked quote ID for guests, quote ID for customers), which starts the PAY. transaction.
- The integration test `RedirectResolverTest` checks the URL, the `mqid` value and the `false` cases.
- The Playwright test `payment-ideal-test` selects `paynl_payment_ideal`, places the order and lands on the `checkout.pay.nl` sandbox page showing "test betaling". The test is skipped when the `PAYNL_TOKENCODE`, `PAYNL_SERVICEID` or `PAYNL_API_TOKEN` credentials are missing.
