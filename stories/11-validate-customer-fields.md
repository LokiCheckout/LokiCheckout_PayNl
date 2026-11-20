---
status: complete
---

# Validate PAY. customer fields

As a shopper, I want to be told right away when a date of birth, CoC number or VAT number is invalid, so that PAY. does not reject my payment.

## Acceptance criteria
- The validators `paynl_dob`, `paynl_coc` and `paynl_vat` are registered in `Loki\Components\Validator\ValidatorRegistry`.
- `paynl_dob` accepts an empty value and rejects an invalid date or a date that is not in the past.
- `paynl_coc` and `paynl_vat` accept an empty value and reject values shorter than 8 characters ("Please enter a valid CoC number", "Please enter a valid VAT number").
- The integration test `ValidatorsTest` covers all three validators. The Playwright test `customer-fields-test` checks the CoC error message.
