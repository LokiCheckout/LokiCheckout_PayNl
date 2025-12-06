<?php

declare(strict_types=1);

namespace LokiCheckout\PayNl\Test\Integration\ViewModel;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Test\Fixture\Customer as CustomerFixture;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\TestFramework\Fixture\Config as ConfigFixture;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use PHPUnit\Framework\TestCase;
use Yireo\IntegrationTestHelper\Test\Integration\Traits\GetObjectManager;
use LokiCheckout\PayNl\ViewModel\IdealFastCheckout;

class IdealFastCheckoutTest extends TestCase
{
    use GetObjectManager;

    final public function testInstantiation(): void
    {

        $idealFastCheckout = $this->getInstance();
        $this->assertInstanceOf(IdealFastCheckout::class, $idealFastCheckout);
    }

    #[ConfigFixture('payment/paynl_payment_ideal/fast_checkout_guest_only', 0, 'store', 'default')]
    final public function testIsEnabled(): void
    {
        $idealFastCheckout = $this->getInstance();
        $actual = $idealFastCheckout->enabled();
        $this->assertEquals(true, $actual);
    }

    #[
        DataFixture(CustomerFixture::class, ['email' => 'info@example.com'], as: 'customer'),
        ConfigFixture('payment/paynl_payment_ideal/fast_checkout_guest_only', 1, 'store', 'default')
    ]
    final public function testIsDisabled(): void
    {
        $fixtures = DataFixtureStorageManager::getStorage();
        $customer = $fixtures->get('customer');
        $this->assertInstanceOf(Customer::class, $customer);

        $customerSession = $this->getObjectManager()->get(CustomerSession::class);
        $customerSession->loginById($customer->getId());

        $idealFastCheckout = $this->getInstance();
        $actual = $idealFastCheckout->enabled();
        $this->assertEquals(false, $actual);
    }

    #[ConfigFixture('payment/paynl_payment_ideal/fast_checkout_show_modal', 1, 'store', 'default')]
    final public function testModalIsEnabled(): void
    {
        $idealFastCheckout = $this->getInstance();
        $actual = $idealFastCheckout->modalEnabled();
        $this->assertEquals(true, $actual);
    }

    #[ConfigFixture('payment/paynl_payment_ideal/fast_checkout_show_modal', 0)]
    #[ConfigFixture('payment/paynl_payment_ideal/fast_checkout_show_modal', 0, 'store', 'default')]
    final public function testModalIsDisabled(): void
    {
        $scopeConfig = $this->getObjectManager()->get(ScopeConfigInterface::class);
        $this->assertEquals(0, $scopeConfig->getValue('payment/paynl_payment_ideal/fast_checkout_show_modal'));

        $idealFastCheckout = $this->getInstance();
        $actual = $idealFastCheckout->modalEnabled();
        $this->assertEquals(false, $actual);
    }

    private function getInstance(): IdealFastCheckout
    {
        return $this->getObjectManager()->create(IdealFastCheckout::class);
    }
}
