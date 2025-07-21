<?php

declare(strict_types=1);

namespace LokiCheckout\PayNl\Test\Integration;

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
            'LokiCheckout_PayNl',
            'LokiCheckout_Core',
            'Loki_Components',
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
