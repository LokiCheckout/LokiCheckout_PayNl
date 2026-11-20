<?php

declare(strict_types=1);

namespace LokiCheckout\PayNl\Test\Integration\Payment\Redirect;

use LokiCheckout\Core\Payment\Redirect\RedirectResolverListing;
use LokiCheckout\Core\Step\FinalStep\RedirectContext;
use LokiCheckout\PayNl\Payment\Redirect\RedirectResolver;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Model\Order;
use Magento\TestFramework\Fixture\AppArea;
use PHPUnit\Framework\TestCase;

#[AppArea('frontend')]
final class RedirectResolverTest extends TestCase
{
    public function testResolverIsRegistered(): void
    {
        $listing = ObjectManager::getInstance()->create(RedirectResolverListing::class);
        $resolvers = $listing->getRedirectResolvers();
        $payNlResolvers = array_filter($resolvers, fn ($resolver) => $resolver instanceof RedirectResolver);

        $this->assertCount(1, $payNlResolvers);
    }

    public function testPayNlOrderIsRedirectedToPayNlController(): void
    {
        $method = $this->getPaymentHelper()->getMethodInstance('paynl_payment_ideal');
        $before = time();

        $url = $this->getInstance()->resolve($this->getRedirectContext($method, $this->createOrder()));

        $this->assertIsString($url);
        $this->assertStringContainsString('paynl/checkout/redirect', $url);
        $this->assertMatchesRegularExpression('/[?&]nocache=(\d+)/', $url);
        preg_match('/[?&]nocache=(\d+)/', $url, $match);
        $this->assertGreaterThanOrEqual($before, (int)$match[1]);
        $this->assertStringStartsWith('http', $url);
    }

    public function testOtherPayNlMethodsAreRedirectedToo(): void
    {
        $method = $this->getPaymentHelper()->getMethodInstance('paynl_payment_billink');

        $url = $this->getInstance()->resolve($this->getRedirectContext($method, $this->createOrder()));

        $this->assertIsString($url);
        $this->assertStringContainsString('paynl/checkout/redirect', $url);
    }

    public function testOtherMethodIsNotRedirected(): void
    {
        $method = $this->getPaymentHelper()->getMethodInstance('checkmo');

        $this->assertFalse($this->getInstance()->resolve($this->getRedirectContext($method, $this->createOrder())));
    }

    public function testPayNlMethodWithoutOrderIsNotRedirected(): void
    {
        $method = $this->getPaymentHelper()->getMethodInstance('paynl_payment_ideal');

        $this->assertFalse($this->getInstance()->resolve($this->getRedirectContext($method, false)));
    }

    public function testNoLastOrderIsNotRedirected(): void
    {
        ObjectManager::getInstance()->get(CheckoutSession::class)->setLastOrderId(null);
        $redirectContext = ObjectManager::getInstance()->create(RedirectContext::class);

        $this->assertFalse($this->getInstance()->resolve($redirectContext));
    }

    public function testExceptionsAreNotRedirected(): void
    {
        $redirectContext = $this->createMock(RedirectContext::class);
        $redirectContext->method('getPaymentMethod')->willThrowException(new LocalizedException(__('Error')));

        $this->assertFalse($this->getInstance()->resolve($redirectContext));
    }

    private function getRedirectContext(MethodInterface $method, false|Order $order): RedirectContext
    {
        $redirectContext = $this->createMock(RedirectContext::class);
        $redirectContext->method('getPaymentMethod')->willReturn($method);
        $redirectContext->method('getOrder')->willReturn($order);

        return $redirectContext;
    }

    private function createOrder(): Order
    {
        return ObjectManager::getInstance()->create(Order::class);
    }

    private function getPaymentHelper(): PaymentHelper
    {
        return ObjectManager::getInstance()->get(PaymentHelper::class);
    }

    private function getInstance(): RedirectResolver
    {
        return ObjectManager::getInstance()->create(RedirectResolver::class);
    }
}
