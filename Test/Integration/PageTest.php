<?php

declare(strict_types=1);

namespace LokiCheckout\PayNl\Test\Integration;

use LokiCheckout\Core\Test\Integration\LokiCheckoutTestCase;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\LayoutInterface;
use Magento\Quote\Test\Fixture\AddProductToCart as AddProductToCartFixture;
use Magento\Quote\Test\Fixture\GuestCart as GuestCartFixture;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\Config as ConfigFixture;
use Magento\TestFramework\Fixture\DataFixture;

#[
    DataFixture(ProductFixture::class, ['sku' => 'simple-product-001'], as: 'product'),
    DataFixture(GuestCartFixture::class, as: 'cart'),
    DataFixture(AddProductToCartFixture::class, ['cart_id' => '$cart.id$', 'product_id' => '$product.id$']),
    AppArea('frontend'),
]
class PageTest extends LokiCheckoutTestCase
{
    final public function testForCss(): void
    {
        $this->dispatchToCheckout();
        $body = (string)$this->getResponse()->getBody();
        $this->assertStringNotContainsString('css/payFastCheckout.css', $body);
        $this->assertStringNotContainsString('css/payCheckout.css', $body);
    }

    #[ConfigFixture('loki_checkout/paynl/ideal_fast_checkout_enabled', 1, 'store', 'default')]
    #[ConfigFixture('payment/paynl_payment_ideal/fast_checkout_show_modal', 0, 'store', 'default')]
    final public function testSimpleFastCheckoutFormPostsToStoreUrl(): void
    {
        $this->dispatchToCheckout();

        $html = $this->renderFastCheckout();
        $this->assertStringContainsString('action="' . $this->getFastCheckoutUrl() . '"', $html);
        $this->assertStringNotContainsString('x-data="LokiModal"', $html);
    }

    #[ConfigFixture('loki_checkout/paynl/ideal_fast_checkout_enabled', 1, 'store', 'default')]
    #[ConfigFixture('payment/paynl_payment_ideal/fast_checkout_show_modal', 1, 'store', 'default')]
    final public function testModalFastCheckoutFormPostsToStoreUrl(): void
    {
        $this->dispatchToCheckout();

        $html = $this->renderFastCheckout();
        $this->assertStringContainsString('action="' . $this->getFastCheckoutUrl() . '"', $html);
        $this->assertStringContainsString('x-data="LokiModal"', $html);
    }

    #[ConfigFixture('loki_checkout/paynl/ideal_fast_checkout_enabled', 0, 'store', 'default')]
    final public function testFastCheckoutBlockIsAbsentWhenDisabled(): void
    {
        $this->dispatchToCheckout();
        $body = (string)$this->getResponse()->getBody();

        $this->assertFalse($this->getObjectManager()->get(LayoutInterface::class)
            ->getBlock('loki-checkout-pay-nl.fast-checkout.ideal'));
        $this->assertStringNotContainsString('paynl/checkout/fastcheckoutstart', $body);
        $this->assertStringNotContainsString('paynl/checkout/fastcheckoutstart', $this->renderFastCheckout());
    }

    private function renderFastCheckout(): string
    {
        $block = $this->getObjectManager()->get(LayoutInterface::class)->getBlock('loki-checkout.fast_checkout');
        $this->assertInstanceOf(AbstractBlock::class, $block);

        return (string)$block->toHtml();
    }

    private function getFastCheckoutUrl(): string
    {
        $url = $this->getObjectManager()->get(UrlInterface::class)->getUrl('paynl/checkout/fastcheckoutstart');
        $this->assertStringStartsWith('http', $url);
        $this->assertStringEndsWith('paynl/checkout/fastcheckoutstart/', $url);

        return $url;
    }
}
