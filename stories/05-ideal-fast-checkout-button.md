---
status: complete
---

# iDEAL Fast Checkout button

As a shopper, I want a "Checkout with iDEAL" button in the checkout, so that I can check out faster using my iDEAL profile.

## Acceptance criteria
- The block `loki-checkout-pay-nl.fast-checkout.ideal` is added to the `loki-checkout.fast_checkout` block of the `loki_checkout` layout.
- The block is only added when `loki_checkout/paynl/ideal_fast_checkout_enabled` is enabled.
- Nothing is rendered when the PAY. `FastCheckout::getVisibility()` returns `false`. For example, a logged-in customer sees no button when `payment/paynl_payment_ideal/fast_checkout_guest_only` is `1`.
- The button is enabled when `fast_checkout_guest_only` is `0`.
- When the modal is disabled (`payment/paynl_payment_ideal/fast_checkout_show_modal` is `0`), a simple form is rendered:
  - It posts to `/paynl/checkout/fastcheckoutstart`.
  - It has hidden fields `selected_estimate_country` and `selected_estimate_zip`, filled from the country and postcode of the quote's shipping address.
  - It includes the Magento form key.
  - The button shows the label "Checkout with" and the iDEAL logo (`LokiCheckout_PayNl::images/ideal.svg`) and submits the form through `LokiForm`.
- The integration test `IdealFastCheckoutTest` covers the enabled and disabled visibility cases.
