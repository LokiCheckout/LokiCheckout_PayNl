---
status: complete
---

# Use checkout fields for PAY. customer data

As a store owner, I want PAY. to receive the date of birth, CoC and VAT number that the shopper already entered elsewhere in the checkout, so that shoppers do not need to enter them twice (PAY. option `3`).

## Acceptance criteria
- The observer `LokiCheckout\PayNl\Observer\CopyCheckoutFieldsToPayment` listens to `sales_model_service_quote_submit_before`.
- For `paynl_*` methods it fills empty `dob`, `cocnumber` and `vatnumber` values of the quote and order payment.
- The values come from the billing address attributes `paynl_dob`, `paynl_coc_number` and `paynl_vat_number`, with the quote `customer_dob` and the billing `vat_id` as fallbacks. The date of birth is stored as `dd-mm-yyyy`.
- Values entered in the payment form are never overwritten. Other payment methods are left alone.
- The integration test `CopyCheckoutFieldsToPaymentTest` covers this.
