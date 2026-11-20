---
status: complete
---

# Customer fields for PAY. pay later and B2B methods

As a shopper, I want to enter the extra details that PAY. needs (date of birth, CoC number, VAT number, company name, payment terms), so that I can pay with a pay later or B2B method.

## Acceptance criteria
- The methods `paynl_payment_` + `afterpay`, `afterpay_international`, `alma`, `biller`, `billink`, `capayable_gespreid`, `in3business`, `klarna`, `klarnakp`, `spraypay`, `xafaxmynetpay` and `yourgift` get a form `loki-checkout.payment.methods.<code>.form` with the fields `dob` (date input), `cocnumber` and `vatnumber`.
- Biller also gets `companyfield`, Billink also gets the checkbox `billink_agree`.
- The PAY. settings `payment/<code>/showdob`, `showkvk`, `showvat` and `showcompanyfield` decide per field: `0` and `3` hidden, `1` optional, `2` required.
- The VAT field is only shown when the billing address has a company or the company field is shown. The company field is hidden when the billing address already has a company.
- `billink_agree` is shown (and required) when `showkvk` is above `0`.
- Values are stored in the quote payment additional information with the PAY. keys `dob` (`dd-mm-yyyy`), `cocnumber`, `vatnumber`, `companyfield` and `billink_agree` (`0`/`1`). The date of birth defaults to the customer date of birth.
- The integration tests `CustomerFieldsViewModelTest` and `CustomerFieldsRepositoryTest` cover the modes and value formats. The Playwright test `customer-fields-test` checks the Billink fields, a short CoC number, the required terms and hidden fields.
