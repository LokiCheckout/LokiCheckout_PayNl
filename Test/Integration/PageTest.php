<?php

declare(strict_types=1);

namespace LokiCheckout\PayNl\Test\Integration;

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Quote\Test\Fixture\AddProductToCart as AddProductToCartFixture;
use Magento\Quote\Test\Fixture\GuestCart as GuestCartFixture;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\DataFixture;
use Yireo\IntegrationTestHelper\Test\Integration\Traits\AssertModuleIsEnabled;
use Yireo\IntegrationTestHelper\Test\Integration\Traits\AssertModuleIsRegistered;
use LokiCheckout\Core\Test\Integration\LokiCheckoutPageTestCase;

#[
    DataFixture(ProductFixture::class, ['sku' => 'simple-product-001'], as: 'product'),
    DataFixture(GuestCartFixture::class, as: 'cart'),
    DataFixture(AddProductToCartFixture::class, ['cart_id' => '$cart.id$', 'product_id' => '$product.id$']),
    AppArea('frontend')
]
final class PageTest extends LokiCheckoutPageTestCase
{
    use AssertModuleIsEnabled;
    use AssertModuleIsRegistered;

    final public function testForCss(): void
    {
        $body = (string)$this->getResponse()->getBody();
        $this->assertStringNotContainsString('css/payFastCheckout.css', $body);
        $this->assertStringNotContainsString('css/payCheckout.css', $body);
    }
}
