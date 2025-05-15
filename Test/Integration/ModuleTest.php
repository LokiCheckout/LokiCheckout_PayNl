<?php

declare(strict_types=1);

namespace Yireo\LokiCheckoutPayNl\Test\Integration;

use PHPUnit\Framework\TestCase;
use Yireo\IntegrationTestHelper\Test\Integration\Traits\AssertModuleIsEnabled;
use Yireo\IntegrationTestHelper\Test\Integration\Traits\AssertModuleIsRegistered;

final class ModuleTest extends TestCase
{
    use AssertModuleIsEnabled;
    use AssertModuleIsRegistered;

    final public function testModule(): void
    {
        $moduleNames = [
            'Yireo_LokiCheckoutPayNl',
            'Yireo_LokiCheckout',
            'Yireo_LokiComponents',
            'Magento_Backend',
            'Magento_Quote',
            'Magento_Sales',
            'Magento_Store',
            'Paynl_Payment',
        ];

        foreach ($moduleNames as $moduleName) {
            $this->assertModuleIsEnabled($moduleName);
            $this->assertModuleIsRegistered($moduleName);
        }
    }
}
