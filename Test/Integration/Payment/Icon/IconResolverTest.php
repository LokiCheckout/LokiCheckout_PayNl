<?php

declare(strict_types=1);

namespace Yireo\LokiCheckoutPayNl\Test\Integration\Payment\Icon;

use Magento\Framework\App\ObjectManager;
use PHPUnit\Framework\TestCase;
use Yireo\IntegrationTestHelper\Test\Integration\Traits\GetObjectManager;
use Yireo\LokiCheckout\Payment\Icon\IconResolverContext;
use Yireo\LokiCheckoutPayNl\Payment\Icon\IconResolver;

final class IconResolverTest extends TestCase
{
    use GetObjectManager;

    final public function testInstantiation(): void
    {
        $iconResolver = $this->getInstance();
        $this->assertInstanceOf(IconResolver::class, $iconResolver);
    }

    final public function testResolve(): void
    {
        $iconResolver = $this->getInstance();
        $iconResolverContext = $this->getObjectManager()->create(IconResolverContext::class, [
            'paymentMethodCode' => 'paynl_payment_ideal',
        ]);
        $actual = $iconResolver->resolve($iconResolverContext);
        $this->assertStringContainsString('<img src=', $actual);
    }

    private function getInstance(): IconResolver
    {
        $objectManager = ObjectManager::getInstance();
        return $objectManager->create(IconResolver::class);
    }
}
