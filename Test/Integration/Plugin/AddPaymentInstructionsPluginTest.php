<?php

declare(strict_types=1);

namespace LokiCheckout\PayNl\Test\Integration\Plugin;

use LokiCheckout\Core\Component\Checkout\Billing\PaymentMethods\PaymentMethodsViewModel;
use LokiCheckout\PayNl\Plugin\AddPaymentInstructionsPlugin;
use LokiCheckout\PayNl\Test\Integration\Traits\PayNlTestTrait;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\Config as ConfigFixture;
use PHPUnit\Framework\TestCase;
use Yireo\IntegrationTestHelper\Test\Integration\Traits\AssertInterceptorPluginIsRegistered;

#[AppArea('frontend')]
class AddPaymentInstructionsPluginTest extends TestCase
{
    use PayNlTestTrait;
    use AssertInterceptorPluginIsRegistered;

    public function testPluginIsRegistered(): void
    {
        $this->assertInterceptorPluginIsRegistered(
            PaymentMethodsViewModel::class,
            AddPaymentInstructionsPlugin::class,
            'LokiCheckout_PayNl::addPaymentInstructions'
        );
    }

    #[ConfigFixture('payment/paynl_payment_overboeking/instructions', 'Transfer the money', 'store', 'default')]
    public function testConfiguredInstructionsAreAdded(): void
    {
        $this->assertSame('Transfer the money', $this->getInstructions('', 'paynl_payment_overboeking'));
    }

    #[ConfigFixture('payment/paynl_payment_overboeking/instructions', 'Default instructions')]
    #[ConfigFixture('payment/paynl_payment_overboeking/instructions', 'Store instructions', 'store', 'default')]
    public function testInstructionsAreReadFromStoreScope(): void
    {
        $this->assertSame('Store instructions', $this->getInstructions('', 'paynl_payment_overboeking'));
    }

    #[ConfigFixture('payment/paynl_payment_overboeking/instructions', 'Transfer the money', 'store', 'default')]
    public function testExistingInstructionsAreKept(): void
    {
        $this->assertSame('Existing', $this->getInstructions('Existing', 'paynl_payment_overboeking'));
    }

    #[ConfigFixture('payment/paynl_payment_ideal/instructions', '', 'store', 'default')]
    public function testEmptyInstructionsStayEmpty(): void
    {
        $this->assertSame('', $this->getInstructions('', 'paynl_payment_ideal'));
    }

    #[ConfigFixture('payment/checkmo/instructions', 'Send a check', 'store', 'default')]
    public function testOtherMethodsAreIgnored(): void
    {
        $this->assertSame('', $this->getInstructions('', 'checkmo'));
    }

    private function getInstructions(string $result, string $paymentCode): string
    {
        $plugin = $this->getTestObjectManager()->create(AddPaymentInstructionsPlugin::class);
        $subject = $this->createMock(PaymentMethodsViewModel::class);

        return $plugin->afterGetPaymentInstructions($subject, $result, $paymentCode);
    }
}
