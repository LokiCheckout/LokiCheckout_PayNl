---
status: complete
---

# Register PAY. as a Loki Checkout module

As a store developer, I want the PAY. integration to be registered with Loki Checkout, so that the `Paynl_Payment` module works inside the Loki Checkout.

## Acceptance criteria
- `LokiCheckout_PayNl` is added to the `modules` argument of `LokiCheckout\Core\Config\LokiCheckoutModules` via `etc/di.xml`.
- The module loads after `LokiCheckout_Core`, `Loki_Components`, `Loki_CssUtils`, `Paynl_Payment` and the required Magento modules (`Magento_Backend`, `Magento_Config`, `Magento_Customer`, `Magento_Quote`, `Magento_Sales`, `Magento_Store`).
- The composer package `loki-checkout/magento2-pay-nl` has the keyword `loki-checkout-category-payment` and requires `paynl/magento2-plugin` `^3.0 | ^4.0`.
- The integration test `ModuleTest` checks that the module and its dependencies are registered and enabled.
