---
status: complete
---

# Only show PAY. methods that are allowed

As a store owner, I want the PAY. availability settings to work in the Loki Checkout, so that shoppers only see the PAY. methods I allow for them.

## Acceptance criteria
- The plugin `LokiCheckout\PayNl\Plugin\PaymentMethodsRepositoryPlugin` filters `PaymentMethodsRepository::getPaymentMethods()` in the frontend. Methods not starting with `paynl_` are not touched.
- `showforgroup` (comma separated) must contain the customer group of the quote (guests are group `0`).
- `disallowedshipping` (comma separated) must not contain the selected shipping method.
- `showforcompany`: `1` only without billing company, `2` only with billing company.
- The method's `isCurrentIpValid()` and `isCurrentAgentValid()` must return `true`.
- Changing the billing or shipping company refreshes the payment methods.
- The integration test `PaymentMethodsRepositoryPluginTest` covers each rule. The Playwright test `payment-filter-test` checks `showforcompany`.
