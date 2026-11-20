<?php

declare(strict_types=1);

namespace LokiCheckout\PayNl\Test\Integration\Plugin;

use LokiCheckout\Core\Component\Checkout\Billing\PaymentMethods\PaymentMethodsRepository;
use LokiCheckout\Core\Test\Integration\LokiCheckoutTestCase;
use LokiCheckout\Core\Test\Integration\Trait\SaveShippingAddressValues;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Quote\Test\Fixture\AddProductToCart as AddProductToCartFixture;
use Magento\Quote\Test\Fixture\GuestCart as GuestCartFixture;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\Config as ConfigFixture;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DbIsolation;

#[DbIsolation(true)]
#[DataFixture(ProductFixture::class, as: 'product')]
#[DataFixture(GuestCartFixture::class, as: 'cart')]
#[DataFixture(AddProductToCartFixture::class, ['cart_id' => '$cart.id$', 'product_id' => '$product.id$'])]
#[AppArea('frontend')]
class DefaultPaymentMethodTest extends LokiCheckoutTestCase
{
    use SaveShippingAddressValues;

    #[ConfigFixture('payment/checkmo/active', 1, 'store', 'default')]
    #[ConfigFixture('payment/banktransfer/active', 1, 'store', 'default')]
    #[ConfigFixture('loki_checkout/billing/select_default_payment_method', 0, 'store', 'default')]
    #[ConfigFixture('payment/paynl/default_payment_option', 'banktransfer', 'store', 'default')]
    public function testDefaultPaymentOptionWinsWhenLokiSwitchIsOff(): void
    {
        $this->assertSame('banktransfer', $this->getRepository()->getDefaultPayment());
    }

    #[ConfigFixture('payment/checkmo/active', 1, 'store', 'default')]
    #[ConfigFixture('payment/banktransfer/active', 1, 'store', 'default')]
    #[ConfigFixture('payment/checkmo/sort_order', 1, 'store', 'default')]
    #[ConfigFixture('payment/banktransfer/sort_order', 2, 'store', 'default')]
    #[ConfigFixture('loki_checkout/billing/select_default_payment_method', 1, 'store', 'default')]
    #[ConfigFixture('payment/paynl/default_payment_option', 'banktransfer', 'store', 'default')]
    public function testDefaultPaymentOptionWinsWhenLokiSwitchIsOn(): void
    {
        $repository = $this->getRepository();
        $codes = array_map(fn ($method) => $method->getCode(), $repository->getPaymentMethods());
        $this->assertNotSame('banktransfer', $codes[0]);

        $this->assertSame('banktransfer', $repository->getDefaultPayment());
    }

    #[ConfigFixture('payment/checkmo/active', 1, 'store', 'default')]
    #[ConfigFixture('payment/banktransfer/active', 0, 'store', 'default')]
    #[ConfigFixture('loki_checkout/billing/select_default_payment_method', 0, 'store', 'default')]
    #[ConfigFixture('payment/paynl/default_payment_option', 'banktransfer', 'store', 'default')]
    public function testDefaultPaymentOptionIsIgnoredWhenNotAvailable(): void
    {
        $this->assertSame('', $this->getRepository()->getDefaultPayment());
    }

    private function getRepository(): PaymentMethodsRepository
    {
        $this->saveShippingAddressValues(['country_id' => 'US', 'region' => 'Texas', 'postcode' => '78701']);
        $this->getCart();

        $repository = $this->getCheckoutComponent('loki-checkout.payment.methods')->getRepository();
        $this->assertInstanceOf(PaymentMethodsRepository::class, $repository);

        return $repository;
    }
}
