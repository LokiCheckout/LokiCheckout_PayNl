<?php

declare(strict_types=1);

namespace Yireo\LokiCheckoutPayNl\Test\Integration;

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Quote\Test\Fixture\GuestCart as GuestCartFixture;
use Magento\TestFramework\Fixture\DataFixture;
use Yireo\IntegrationTestHelper\Test\Integration\Traits\AssertModuleIsEnabled;
use Yireo\IntegrationTestHelper\Test\Integration\Traits\AssertModuleIsRegistered;
use Yireo\LokiCheckout\Test\Integration\LokiCheckoutPageTestCase;

final class PageTest extends LokiCheckoutPageTestCase
{
    use AssertModuleIsEnabled;
    use AssertModuleIsRegistered;

    #[DataFixture(ProductFixture::class, ['sku' => 'simple-product-001'], as: 'product')]
    #[DataFixture(GuestCartFixture::class, as: 'cart')]
    final public function testForCss(): void
    {
        $body = (string)$this->getResponse()->getBody();
        $this->assertStringNotContainsString('css/payFastCheckout.css', $body);
        $this->assertStringNotContainsString('css/payCheckout.css', $body);
    }
}
