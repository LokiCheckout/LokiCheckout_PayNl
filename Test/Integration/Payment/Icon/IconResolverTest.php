<?php

declare(strict_types=1);

namespace LokiCheckout\PayNl\Test\Integration\Payment\Icon;

use LokiCheckout\Core\Payment\Icon\IconResolverContext;
use LokiCheckout\Core\Payment\Icon\IconResolverListing;
use LokiCheckout\PayNl\Payment\Icon\IconResolver;
use Magento\Framework\App\ObjectManager;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\Config as ConfigFixture;
use PHPUnit\Framework\TestCase;

#[AppArea('frontend')]
final class IconResolverTest extends TestCase
{
    public function testResolverIsRegistered(): void
    {
        $resolvers = ObjectManager::getInstance()->create(IconResolverListing::class)->getIconResolvers();
        $payNlResolvers = array_filter($resolvers, fn ($resolver) => $resolver instanceof IconResolver);

        $this->assertCount(1, $payNlResolvers);
    }

    #[ConfigFixture('payment/paynl_payment_ideal/title', 'iDEAL by PAY.', 'store', 'default')]
    public function testIconUsesMethodTitleAsAlt(): void
    {
        $actual = $this->resolve('paynl_payment_ideal');

        $this->assertIsString($actual);
        $this->assertStringContainsString('<img src=', $actual);
        $this->assertStringContainsString('Paynl_Payment/logos/', $actual);
        $this->assertStringContainsString('alt="iDEAL by PAY."', html_entity_decode($actual));
    }

    #[ConfigFixture('payment/paynl_payment_billink/title', 'Achteraf betalen', 'store', 'default')]
    public function testTitleIsReadPerMethod(): void
    {
        $this->assertSame('Achteraf betalen', $this->getInstance()->getTitle('paynl_payment_billink'));
        $this->assertStringContainsString('alt="Achteraf betalen"', html_entity_decode((string)$this->resolve('paynl_payment_billink')));
    }

    #[ConfigFixture('payment/paynl_payment_ideal/title', '  ', 'store', 'default')]
    public function testTitleFallsBackToMethodCode(): void
    {
        $this->assertSame('paynl_payment_ideal', $this->getInstance()->getTitle('paynl_payment_ideal'));
    }

    public function testOtherMethodsAreNotResolved(): void
    {
        $this->assertFalse($this->resolve('checkmo'));
        $this->assertFalse($this->resolve('mollie_methods_ideal'));
    }

    private function resolve(string $paymentMethodCode): false|string
    {
        $iconResolverContext = ObjectManager::getInstance()->create(IconResolverContext::class, [
            'paymentMethodCode' => $paymentMethodCode,
        ]);

        return $this->getInstance()->resolve($iconResolverContext);
    }

    private function getInstance(): IconResolver
    {
        return ObjectManager::getInstance()->create(IconResolver::class);
    }
}
