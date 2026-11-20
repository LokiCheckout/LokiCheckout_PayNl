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
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;
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

    public function testGuestOrderIsRedirectedWithMaskedQuoteId(): void
    {
        $method = $this->getPaymentHelper()->getMethodInstance('paynl_payment_ideal');
        $order = $this->createOrder(42);
        $maskedQuoteId = $this->createMock(QuoteIdToMaskedQuoteIdInterface::class);
        $maskedQuoteId->expects($this->once())->method('execute')->with(42)->willReturn('abcMasked123');

        $url = $this->getInstance($maskedQuoteId)->resolve($this->getRedirectContext($method, $order));

        $this->assertIsString($url);
        $this->assertSame('abcMasked123', $this->getQueryParam($url, 'mqid'));
    }

    public function testGuestOrderFallsBackToNumericQuoteIdWhenMaskIsEmpty(): void
    {
        $method = $this->getPaymentHelper()->getMethodInstance('paynl_payment_ideal');
        $maskedQuoteId = $this->createMock(QuoteIdToMaskedQuoteIdInterface::class);
        $maskedQuoteId->method('execute')->willReturn('');

        $url = $this->getInstance($maskedQuoteId)->resolve($this->getRedirectContext($method, $this->createOrder(42)));

        $this->assertIsString($url);
        $this->assertSame('42', $this->getQueryParam($url, 'mqid'));
    }

    public function testGuestOrderFallsBackToNumericQuoteIdWhenMaskFails(): void
    {
        $method = $this->getPaymentHelper()->getMethodInstance('paynl_payment_ideal');
        $maskedQuoteId = $this->createMock(QuoteIdToMaskedQuoteIdInterface::class);
        $maskedQuoteId->method('execute')->willThrowException(new NoSuchEntityException(__('Not found')));

        $url = $this->getInstance($maskedQuoteId)->resolve($this->getRedirectContext($method, $this->createOrder(42)));

        $this->assertIsString($url);
        $this->assertSame('42', $this->getQueryParam($url, 'mqid'));
    }

    public function testCustomerOrderIsRedirectedWithNumericQuoteId(): void
    {
        $method = $this->getPaymentHelper()->getMethodInstance('paynl_payment_ideal');
        $maskedQuoteId = $this->createMock(QuoteIdToMaskedQuoteIdInterface::class);
        $maskedQuoteId->expects($this->never())->method('execute');

        $url = $this->getInstance($maskedQuoteId)->resolve(
            $this->getRedirectContext($method, $this->createOrder(42, 1))
        );

        $this->assertIsString($url);
        $this->assertSame('42', $this->getQueryParam($url, 'mqid'));
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

    private function createOrder(int $quoteId = 1, ?int $customerId = null): Order
    {
        $order = ObjectManager::getInstance()->create(Order::class);
        $order->setQuoteId($quoteId);
        $order->setCustomerId($customerId);

        return $order;
    }

    private function getQueryParam(string $url, string $name): ?string
    {
        parse_str((string)parse_url($url, PHP_URL_QUERY), $query);
        $value = $query[$name] ?? null;

        return is_string($value) ? $value : null;
    }

    private function getPaymentHelper(): PaymentHelper
    {
        return ObjectManager::getInstance()->get(PaymentHelper::class);
    }

    private function getInstance(?QuoteIdToMaskedQuoteIdInterface $quoteIdToMaskedQuoteId = null): RedirectResolver
    {
        $arguments = [];
        if ($quoteIdToMaskedQuoteId !== null) {
            $arguments['quoteIdToMaskedQuoteId'] = $quoteIdToMaskedQuoteId;
        }

        return ObjectManager::getInstance()->create(RedirectResolver::class, $arguments);
    }
}
