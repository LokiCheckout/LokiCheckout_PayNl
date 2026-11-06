---
status: complete
---

# iDEAL Fast Checkout modal

As a shopper, I want a dialog that explains iDEAL Fast Checkout, so that I can choose between fast checkout and entering my own address.

## Acceptance criteria
- When `payment/paynl_payment_ideal/fast_checkout_show_modal` is enabled, the iDEAL Fast Checkout block renders the modal variant instead of the simple form.
- A "Checkout with" button with the iDEAL logo opens a `<dialog>` (Alpine component `LokiModal`).
- The dialog shows:
  - the iDEAL visual (`Paynl_Payment::images/ideal-visual.webp`);
  - the heading "Order faster with iDEAL fast checkout";
  - the text "Create an iDEAL profile and save your address for your next order".
- The "Fast Checkout" button submits a form to `/paynl/checkout/fastcheckoutstart`. The form contains hidden `selected_estimate_country` and `selected_estimate_zip` fields from the quote's shipping address, plus the form key.
- The "Enter my own address and pay" button closes the dialog.
- The close button (`xmark` icon) closes the dialog.
- The integration test `IdealFastCheckoutTest` checks that the modal setting is enabled and disabled correctly.
