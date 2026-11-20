---
status: complete
---

# Choose a pin terminal and pin moment for PAY. Instore

As a shopper picking up my order in the store, I want to choose a pin terminal and whether I pay now or at pickup, so that I can pay with PAY. Instore.

## Acceptance criteria
- `LokiCheckout\PayNl\Provider\TerminalProvider` reads the terminals from `Paynl\Payment\Model\Paymentmethod\Instore::getPaymentOptions()` and normalises them to `id` and `label` (PAY. 3.x and 4.x formats). No terminals gives an empty list.
- The form `loki-checkout.payment.methods.paynl_payment_instore.form` has a terminal radio list (`payment_option`) and a pin moment radio (`pinmoment`: `0` pay now, `1` pay at pickup).
- The pin moment choice is only shown when `payment/paynl_payment_instore/pinmoment` is `2` and the shipping method is `instore_pickup`. Choosing pay at pickup with a `pinmoment_terminal` stores that terminal.
- The terminal list is hidden when `hide_terminal_selection` is on and a `default_terminal` is set, or when paying at pickup with a pickup terminal. The only terminal or the `default_terminal` is preselected. An unknown terminal gives "Please select a valid terminal".
- Instore is hidden when `pinmoment` is `1` and the shipping method is not `instore_pickup`, and when there are no terminals and no default or pickup terminal.
- The integration tests `TerminalProviderTest`, `InstoreComponentsTest` and `PaymentMethodsRepositoryPluginTest` cover this, with stubbed terminals.
