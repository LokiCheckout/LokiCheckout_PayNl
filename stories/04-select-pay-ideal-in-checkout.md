---
status: complete
---

# Select PAY. iDEAL in the checkout

As a shopper, I want to choose PAY. iDEAL as my payment method in the Loki Checkout, so that I can pay with iDEAL.

## Acceptance criteria
- When `payment/paynl_payment_ideal/active` is enabled and the shipping country is `NL`, the payment option `paynl_payment_ideal` is shown in the Loki Checkout.
- The option can be selected (checked).
- The Playwright test `base-test` covers this.
