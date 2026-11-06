---
status: complete
---

# Leave out PAY.'s Luma checkout assets

As a shopper, I want the Loki Checkout to load without PAY.'s Luma checkout CSS and settings blocks, so that the checkout looks and works correctly.

## Acceptance criteria
- The `loki_checkout` layout removes the stylesheets `Paynl_Payment::css/payFastCheckout.css` and `Paynl_Payment::css/payCheckout.css`.
- The `loki_checkout` layout removes the `Custom_css` and `fast.checkout.settings` blocks.
- The integration test `PageTest` checks that neither stylesheet appears on the checkout page.
