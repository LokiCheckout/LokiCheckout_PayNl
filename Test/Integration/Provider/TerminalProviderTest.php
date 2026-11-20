<?php

declare(strict_types=1);

namespace LokiCheckout\PayNl\Test\Integration\Provider;

use ArrayIterator;
use LokiCheckout\PayNl\Provider\TerminalProvider;
use LokiCheckout\PayNl\Test\Integration\Traits\PayNlTestTrait;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Payment\Model\MethodInterface;
use Magento\TestFramework\Fixture\Config as ConfigFixture;
use Paynl\Payment\Model\Paymentmethod\Instore;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

class TerminalProviderTest extends TestCase
{
    use PayNlTestTrait;

    #[ConfigFixture('payment/paynl_payment_instore/active', 1, 'store', 'default')]
    #[ConfigFixture('payment/paynl/terminals', '[{"code":"TH-1","name":"Terminal 1"},{"code":"TH-2","name":"Terminal 2"}]')]
    public function testTerminalsAreReadFromPayNlConfig(): void
    {
        $terminalProvider = $this->getTerminalProvider();

        $this->assertSame([
            ['id' => 'TH-1', 'label' => 'Terminal 1'],
            ['id' => 'TH-2', 'label' => 'Terminal 2'],
        ], $terminalProvider->getTerminals());
        $this->assertSame(['id' => 'TH-2', 'label' => 'Terminal 2'], $terminalProvider->getTerminalById('TH-2'));
        $this->assertNull($terminalProvider->getTerminalById('TH-3'));
        $this->assertNull($terminalProvider->getTerminalById(''));
    }

    #[ConfigFixture('payment/paynl_payment_instore/active', 0, 'store', 'default')]
    #[ConfigFixture('payment/paynl/terminals', '[{"code":"TH-1","name":"Terminal 1"}]')]
    public function testNoTerminalsWhenInstoreIsInactive(): void
    {
        $this->assertSame([], $this->getTerminalProvider()->getTerminals());
    }

    #[ConfigFixture('payment/paynl_payment_instore/active', 1, 'store', 'default')]
    #[ConfigFixture('payment/paynl/terminals', 'not json')]
    public function testNoTerminalsWithInvalidConfig(): void
    {
        $this->assertSame([], $this->getTerminalProvider()->getTerminals());
    }

    #[ConfigFixture('payment/paynl_payment_instore/default_terminal', 'TH-1', 'store', 'default')]
    #[ConfigFixture('payment/paynl_payment_instore/pinmoment_terminal', ' TH-2 ', 'store', 'default')]
    public function testConfiguredTerminals(): void
    {
        $terminalProvider = $this->getTerminalProvider();

        $this->assertSame('TH-1', $terminalProvider->getDefaultTerminal());
        $this->assertSame('TH-2', $terminalProvider->getPinMomentTerminal());
    }

    #[ConfigFixture('payment/paynl_payment_instore/default_terminal', '', 'store', 'default')]
    #[ConfigFixture('payment/paynl_payment_instore/pinmoment_terminal', '0', 'store', 'default')]
    public function testUnconfiguredTerminals(): void
    {
        $terminalProvider = $this->getTerminalProvider();

        $this->assertSame(TerminalProvider::NO_TERMINAL, $terminalProvider->getDefaultTerminal());
        $this->assertSame(TerminalProvider::NO_TERMINAL, $terminalProvider->getPinMomentTerminal());
    }

    public static function getPaymentOptionCases(): array
    {
        $terminal = new stdClass();
        $terminal->id = 'TH-10';
        $terminal->visibleName = 'Counter';
        $terminal->name = 'Internal name';

        $terminalWithoutLabel = new stdClass();
        $terminalWithoutLabel->id = 42;

        return [
            'false (PAY. 3.x without terminals)' => [false, []],
            'null' => [null, []],
            'string' => ['foobar', []],
            'empty array' => [[], []],
            'stdClass list' => [[$terminal, $terminalWithoutLabel], [
                ['id' => 'TH-10', 'label' => 'Counter'],
                ['id' => '42', 'label' => '42'],
            ]],
            'arrays with name only' => [[['id' => 'TH-11', 'name' => 'Desk']], [
                ['id' => 'TH-11', 'label' => 'Desk'],
            ]],
            'entries without id are skipped' => [[['name' => 'No id'], ['id' => ' '], 'foobar', ['id' => 'TH-12', 'visibleName' => ['x'], 'name' => 'Fallback']], [
                ['id' => 'TH-12', 'label' => 'Fallback'],
            ]],
            'iterator' => [new ArrayIterator([['id' => 'TH-13', 'visibleName' => 'Iterated']]), [
                ['id' => 'TH-13', 'label' => 'Iterated'],
            ]],
        ];
    }

    #[DataProvider('getPaymentOptionCases')]
    public function testPaymentOptionsAreNormalised(mixed $paymentOptions, array $expected): void
    {
        $instore = $this->createMock(Instore::class);
        $instore->method('getPaymentOptions')->willReturn($paymentOptions);

        $this->assertSame($expected, $this->getTerminalProvider($this->getPaymentHelper($instore))->getTerminals());
    }

    public function testTerminalsAreFetchedOnce(): void
    {
        $instore = $this->createMock(Instore::class);
        $instore->expects($this->once())->method('getPaymentOptions')->willReturn([['id' => 'TH-1']]);
        $terminalProvider = $this->getTerminalProvider($this->getPaymentHelper($instore));

        $terminalProvider->getTerminals();
        $terminalProvider->getTerminals();
        $this->assertSame(['id' => 'TH-1', 'label' => 'TH-1'], $terminalProvider->getTerminalById('TH-1'));
    }

    public function testExceptionsResultInNoTerminals(): void
    {
        $instore = $this->createMock(Instore::class);
        $instore->method('getPaymentOptions')->willThrowException(new LocalizedException(__('API down')));

        $this->assertSame([], $this->getTerminalProvider($this->getPaymentHelper($instore))->getTerminals());
    }

    public function testUnknownMethodResultsInNoTerminals(): void
    {
        $paymentHelper = $this->createMock(PaymentHelper::class);
        $paymentHelper->method('getMethodInstance')->willThrowException(new LocalizedException(__('Unknown')));

        $this->assertSame([], $this->getTerminalProvider($paymentHelper)->getTerminals());
    }

    public function testMethodWithoutPaymentOptionsResultsInNoTerminals(): void
    {
        $method = $this->createMock(MethodInterface::class);

        $this->assertSame([], $this->getTerminalProvider($this->getPaymentHelper($method))->getTerminals());
    }

    private function getPaymentHelper(MethodInterface $method): PaymentHelper
    {
        $paymentHelper = $this->createMock(PaymentHelper::class);
        $paymentHelper->method('getMethodInstance')
            ->with(TerminalProvider::METHOD_CODE)
            ->willReturn($method);

        return $paymentHelper;
    }

    private function getTerminalProvider(?PaymentHelper $paymentHelper = null): TerminalProvider
    {
        $arguments = $paymentHelper !== null ? ['paymentHelper' => $paymentHelper] : [];

        return $this->getTestObjectManager()->create(TerminalProvider::class, $arguments);
    }
}
