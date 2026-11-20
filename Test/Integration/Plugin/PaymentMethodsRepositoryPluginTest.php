<?php

declare(strict_types=1);

namespace LokiCheckout\PayNl\Test\Integration\Plugin;

use LokiCheckout\Core\Component\Checkout\Billing\PaymentMethods\PaymentMethodsRepository;
use LokiCheckout\Core\ViewModel\CheckoutState;
use LokiCheckout\PayNl\Plugin\PaymentMethodsRepositoryPlugin;
use LokiCheckout\PayNl\Test\Integration\Traits\PayNlTestTrait;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Model\Quote;
use Magento\TestFramework\Fixture\AppArea;
use Paynl\Payment\Model\Paymentmethod\PaymentMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yireo\IntegrationTestHelper\Test\Integration\Traits\AssertInterceptorPluginIsRegistered;

#[AppArea('frontend')]
class PaymentMethodsRepositoryPluginTest extends TestCase
{
    use PayNlTestTrait;
    use AssertInterceptorPluginIsRegistered;

    private const TERMINALS = '[{"code":"TH-1","name":"Terminal 1"}]';

    protected function setUp(): void
    {
        $this->resetPayNlServices();
    }

    protected function tearDown(): void
    {
        $this->restoreConfig();
        $this->resetPayNlServices();
    }

    public function testPluginIsRegistered(): void
    {
        $this->assertInterceptorPluginIsRegistered(
            PaymentMethodsRepository::class,
            PaymentMethodsRepositoryPlugin::class,
            'LokiCheckout_PayNl::filterPaymentMethods'
        );
    }

    public static function getCustomerGroupCases(): array
    {
        return [
            'no restriction' => ['', 1, true],
            'guest allowed' => ['0,1', 0, true],
            'guest not allowed' => ['1,2', 0, false],
            'group allowed' => ['0, 2', 2, true],
            'group not allowed' => ['0', 1, false],
        ];
    }

    #[DataProvider('getCustomerGroupCases')]
    public function testCustomerGroupRestriction(string $groups, int $customerGroupId, bool $expected): void
    {
        $this->setStoreConfigValue('payment/paynl_payment_billink/showforgroup', $groups);
        $quote = $this->createQuote();
        $quote->setCustomerGroupId($customerGroupId);

        $this->assertSame($expected, $this->isAllowed('paynl_payment_billink', $quote));
    }

    public static function getShippingMethodCases(): array
    {
        return [
            'no restriction' => ['', 'flatrate_flatrate', true],
            'disallowed' => ['tablerate_bestway,flatrate_flatrate', 'flatrate_flatrate', false],
            'other method' => ['tablerate_bestway', 'flatrate_flatrate', true],
            'no shipping method yet' => ['flatrate_flatrate', '', true],
        ];
    }

    #[DataProvider('getShippingMethodCases')]
    public function testDisallowedShippingMethods(string $disallowed, string $shippingMethod, bool $expected): void
    {
        $this->setStoreConfigValue('payment/paynl_payment_billink/disallowedshipping', $disallowed);
        $quote = $this->createQuote(shippingMethod: $shippingMethod);

        $this->assertSame($expected, $this->isAllowed('paynl_payment_billink', $quote));
    }

    public static function getCompanyCases(): array
    {
        return [
            'all, private' => ['0', '', true],
            'all, company' => ['0', 'Yireo', true],
            'private only, private' => ['1', '', true],
            'private only, company' => ['1', 'Yireo', false],
            'company only, private' => ['2', '', false],
            'company only, whitespace' => ['2', '  ', false],
            'company only, company' => ['2', 'Yireo', true],
        ];
    }

    #[DataProvider('getCompanyCases')]
    public function testShowForCompany(string $showForCompany, string $company, bool $expected): void
    {
        $this->setStoreConfigValue('payment/paynl_payment_in3business/showforcompany', $showForCompany);
        $quote = $this->createQuote(company: $company);

        $this->assertSame($expected, $this->isAllowed('paynl_payment_in3business', $quote));
    }

    public function testInvalidClientIpHidesPayNlMethod(): void
    {
        $method = $this->createMock(PaymentMethod::class);
        $method->method('getCode')->willReturn('paynl_payment_ideal');
        $method->method('isCurrentIpValid')->willReturn(false);
        $method->method('isCurrentAgentValid')->willReturn(true);

        $this->assertFalse($this->getPlugin()->isAllowed($method, $this->createQuote()));
    }

    public function testInvalidUserAgentHidesPayNlMethod(): void
    {
        $method = $this->createMock(PaymentMethod::class);
        $method->method('getCode')->willReturn('paynl_payment_ideal');
        $method->method('isCurrentIpValid')->willReturn(true);
        $method->method('isCurrentAgentValid')->willReturn(false);

        $this->assertFalse($this->getPlugin()->isAllowed($method, $this->createQuote()));
    }

    public function testValidClientShowsPayNlMethod(): void
    {
        $method = $this->createMock(PaymentMethod::class);
        $method->method('getCode')->willReturn('paynl_payment_ideal');
        $method->method('isCurrentIpValid')->willReturn(true);
        $method->method('isCurrentAgentValid')->willReturn(true);

        $this->assertTrue($this->getPlugin()->isAllowed($method, $this->createQuote()));
    }

    public function testInstoreWithPinMomentPickupRequiresPickupShipping(): void
    {
        $this->enableInstoreTerminals();
        $this->setStoreConfigValue('payment/paynl_payment_instore/pinmoment', '1');

        $this->assertFalse($this->isAllowed('paynl_payment_instore', $this->createQuote(shippingMethod: 'flatrate_flatrate')));
        $this->assertTrue($this->isAllowed('paynl_payment_instore', $this->createQuote(shippingMethod: 'instore_pickup')));
    }

    public function testInstoreWithPinMomentChoiceDoesNotRequirePickupShipping(): void
    {
        $this->enableInstoreTerminals();
        $this->setStoreConfigValue('payment/paynl_payment_instore/pinmoment', '2');

        $this->assertTrue($this->isAllowed('paynl_payment_instore', $this->createQuote(shippingMethod: 'flatrate_flatrate')));
    }

    public function testInstoreIsShownWithTerminals(): void
    {
        $this->enableInstoreTerminals();

        $this->assertTrue($this->isAllowed('paynl_payment_instore', $this->createQuote()));
    }

    public function testInstoreIsHiddenWithoutTerminals(): void
    {
        $this->setStoreConfigValue('payment/paynl_payment_instore/active', '1');
        $this->setDefaultConfigValue('payment/paynl/terminals', '');
        $this->setStoreConfigValue('payment/paynl_payment_instore/default_terminal', '');
        $this->setStoreConfigValue('payment/paynl_payment_instore/pinmoment_terminal', '0');

        $this->assertFalse($this->isAllowed('paynl_payment_instore', $this->createQuote()));
    }

    public function testInstoreIsShownWithoutTerminalsButWithDefaultTerminal(): void
    {
        $this->setStoreConfigValue('payment/paynl_payment_instore/active', '1');
        $this->setDefaultConfigValue('payment/paynl/terminals', '');
        $this->setStoreConfigValue('payment/paynl_payment_instore/default_terminal', 'TH-5');

        $this->assertTrue($this->isAllowed('paynl_payment_instore', $this->createQuote()));
    }

    public function testInstoreIsShownWithoutTerminalsButWithPinMomentTerminal(): void
    {
        $this->setStoreConfigValue('payment/paynl_payment_instore/active', '1');
        $this->setDefaultConfigValue('payment/paynl/terminals', '');
        $this->setStoreConfigValue('payment/paynl_payment_instore/default_terminal', '0');
        $this->setStoreConfigValue('payment/paynl_payment_instore/pinmoment_terminal', 'TH-6');

        $this->assertTrue($this->isAllowed('paynl_payment_instore', $this->createQuote()));
    }

    public function testOtherMethodsAreNeverFiltered(): void
    {
        foreach (['checkmo', 'free'] as $code) {
            $this->setStoreConfigValue('payment/' . $code . '/showforgroup', '99');
            $this->setStoreConfigValue('payment/' . $code . '/disallowedshipping', 'flatrate_flatrate');
            $this->setStoreConfigValue('payment/' . $code . '/showforcompany', '2');
        }

        $this->assertTrue($this->isAllowed('checkmo', $this->createQuote(shippingMethod: 'flatrate_flatrate')));
        $this->assertTrue($this->isAllowed('free', $this->createQuote(shippingMethod: 'flatrate_flatrate')));
    }

    public function testAfterGetPaymentMethodsFiltersOnlyPayNlMethods(): void
    {
        $this->setStoreConfigValue('payment/paynl_payment_billink/showforcompany', '2');
        $this->setStoreConfigValue('payment/paynl_payment_ideal/showforcompany', '1');
        $this->setStoreConfigValue('payment/checkmo/showforcompany', '2');
        $quote = $this->createQuote();
        $quote->setId(999999);
        $checkoutState = $this->getTestObjectManager()->get(CheckoutState::class);
        $checkoutState->setQuote($quote);
        $checkoutState->setQuoteId(999999);

        $methods = $this->getMethods(['checkmo', 'paynl_payment_billink', 'paynl_payment_ideal']);
        $result = $this->getPlugin()->afterGetPaymentMethods($this->getSubject([]), $methods);

        $this->assertSame(['checkmo', 'paynl_payment_ideal'], $this->getCodes($result));
    }

    public function testConfiguredDefaultPaymentOptionWins(): void
    {
        $this->setStoreConfigValue(PaymentMethodsRepositoryPlugin::DEFAULT_PAYMENT_OPTION_PATH, 'paynl_payment_ideal');
        $subject = $this->getSubject($this->getMethods(['checkmo', 'paynl_payment_ideal']));

        $this->assertSame('paynl_payment_ideal', $this->getPlugin()->afterGetDefaultPayment($subject, 'checkmo'));
        $this->assertSame('paynl_payment_ideal', $this->getPlugin()->afterGetDefaultPayment($subject, ''));
    }

    public function testDefaultPaymentOptionNotInListIsIgnored(): void
    {
        $this->setStoreConfigValue(PaymentMethodsRepositoryPlugin::DEFAULT_PAYMENT_OPTION_PATH, 'paynl_payment_ideal');
        $subject = $this->getSubject($this->getMethods(['checkmo', 'paynl_payment_billink']));

        $this->assertSame('checkmo', $this->getPlugin()->afterGetDefaultPayment($subject, 'checkmo'));
        $this->assertSame('', $this->getPlugin()->afterGetDefaultPayment($subject, ''));
    }

    public function testDefaultPaymentReusesFilteredMethodsWithoutReloading(): void
    {
        $this->setStoreConfigValue(PaymentMethodsRepositoryPlugin::DEFAULT_PAYMENT_OPTION_PATH, 'paynl_payment_ideal');
        $subject = $this->createMock(PaymentMethodsRepository::class);
        $subject->expects($this->never())->method('getPaymentMethods');

        $plugin = $this->getPlugin();
        $plugin->afterGetPaymentMethods($subject, $this->getMethods(['checkmo', 'paynl_payment_ideal']));

        $this->assertSame('paynl_payment_ideal', $plugin->afterGetDefaultPayment($subject, 'checkmo'));
        $this->assertSame('paynl_payment_ideal', $plugin->afterGetDefaultPayment($subject, 'checkmo'));
    }

    public function testDefaultPaymentFollowsRefreshedMethods(): void
    {
        $this->setStoreConfigValue(PaymentMethodsRepositoryPlugin::DEFAULT_PAYMENT_OPTION_PATH, 'paynl_payment_ideal');
        $subject = $this->createMock(PaymentMethodsRepository::class);
        $subject->expects($this->never())->method('getPaymentMethods');

        $plugin = $this->getPlugin();
        $plugin->afterGetPaymentMethods($subject, $this->getMethods(['checkmo', 'paynl_payment_ideal']));
        $this->assertSame('paynl_payment_ideal', $plugin->afterGetDefaultPayment($subject, 'checkmo'));

        $plugin->afterGetPaymentMethods($subject, $this->getMethods(['checkmo']));
        $this->assertSame('checkmo', $plugin->afterGetDefaultPayment($subject, 'checkmo'));
    }

    public function testDefaultPaymentLoadsMethodsOnlyOnce(): void
    {
        $this->setStoreConfigValue(PaymentMethodsRepositoryPlugin::DEFAULT_PAYMENT_OPTION_PATH, 'paynl_payment_ideal');
        $subject = $this->createMock(PaymentMethodsRepository::class);
        $subject->expects($this->once())->method('getPaymentMethods')
            ->willReturn($this->getMethods(['checkmo', 'paynl_payment_ideal']));

        $plugin = $this->getPlugin();
        $this->assertSame('paynl_payment_ideal', $plugin->afterGetDefaultPayment($subject, 'checkmo'));
        $this->assertSame('paynl_payment_ideal', $plugin->afterGetDefaultPayment($subject, 'checkmo'));
    }

    public static function getEmptyDefaultPaymentOptions(): array
    {
        return [
            'empty' => [''],
            'zero' => ['0'],
            'whitespace' => ['  '],
        ];
    }

    #[DataProvider('getEmptyDefaultPaymentOptions')]
    public function testEmptyDefaultPaymentOptionKeepsResult(string $value): void
    {
        $this->setStoreConfigValue(PaymentMethodsRepositoryPlugin::DEFAULT_PAYMENT_OPTION_PATH, $value);
        $subject = $this->getSubject($this->getMethods(['checkmo', 'paynl_payment_ideal']));

        $this->assertSame('checkmo', $this->getPlugin()->afterGetDefaultPayment($subject, 'checkmo'));
    }

    private function enableInstoreTerminals(): void
    {
        $this->setStoreConfigValue('payment/paynl_payment_instore/active', '1');
        $this->setDefaultConfigValue('payment/paynl/terminals', self::TERMINALS);
    }

    private function isAllowed(string $code, Quote $quote): bool
    {
        $method = $this->createMock(MethodInterface::class);
        $method->method('getCode')->willReturn($code);

        return $this->getPlugin()->isAllowed($method, $quote);
    }

    private function createQuote(string $company = '', string $shippingMethod = ''): Quote
    {
        $quote = $this->getTestObjectManager()->create(Quote::class);
        $quote->setCustomerGroupId(0);
        $quote->getBillingAddress()->setCompany($company);
        $quote->getShippingAddress()->setShippingMethod($shippingMethod);

        return $quote;
    }

    private function getPlugin(): PaymentMethodsRepositoryPlugin
    {
        return $this->getTestObjectManager()->create(PaymentMethodsRepositoryPlugin::class);
    }

    private function getSubject(array $methods): PaymentMethodsRepository
    {
        $subject = $this->createMock(PaymentMethodsRepository::class);
        $subject->method('getPaymentMethods')->willReturn($methods);

        return $subject;
    }

    private function getMethods(array $codes): array
    {
        $methods = [];
        foreach ($codes as $code) {
            $method = $this->createMock(MethodInterface::class);
            $method->method('getCode')->willReturn($code);
            $methods[] = $method;
        }

        return $methods;
    }

    private function getCodes(array $methods): array
    {
        return array_map(fn (MethodInterface $method) => $method->getCode(), $methods);
    }
}
