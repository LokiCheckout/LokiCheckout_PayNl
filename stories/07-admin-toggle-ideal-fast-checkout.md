---
status: complete
---

# Admin setting for iDEAL Fast Checkout

As a store administrator, I want to switch iDEAL Fast Checkout in the Loki Checkout on or off, so that I control whether it is offered to shoppers.

## Acceptance criteria
- The admin configuration has a field "Enable iDEAL Fast Checkout" (Yes/No) in section `loki_checkout`, group "PAY. options".
- The field has a tooltip explaining that it enables or disables the PAY iDEAL Fast Checkout options in the checkout.
- The field can be set at default and website scope. It is not available at store view scope.
- The default value is `1` (enabled).
- When it is set to No, the iDEAL Fast Checkout block is not added to the checkout layout.
