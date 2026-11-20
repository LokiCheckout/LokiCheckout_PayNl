<?php

declare(strict_types=1);

namespace LokiCheckout\PayNl\Test\Integration\Component\Instore;

use Loki\Components\Component\ComponentInterface;
use LokiCheckout\PayNl\Component\Checkout\Payment\Method\Instore\PinMomentRepository;
use LokiCheckout\PayNl\Component\Checkout\Payment\Method\Instore\PinMomentViewModel;
use LokiCheckout\PayNl\Component\Checkout\Payment\Method\Instore\TerminalRepository;
use LokiCheckout\PayNl\Component\Checkout\Payment\Method\Instore\TerminalViewModel;
use LokiCheckout\PayNl\Test\Integration\Traits\PayNlTestTrait;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Checkout\Test\Fixture\SetBillingAddress as SetBillingAddressFixture;
use Magento\Checkout\Test\Fixture\SetDeliveryMethod as SetDeliveryMethodFixture;
use Magento\Checkout\Test\Fixture\SetGuestEmail as SetGuestEmailFixture;
use Magento\Checkout\Test\Fixture\SetShippingAddress as SetShippingAddressFixture;
use Magento\Quote\Model\Quote;
use Magento\Quote\Test\Fixture\AddProductToCart as AddProductToCartFixture;
use Magento\Quote\Test\Fixture\GuestCart as GuestCartFixture;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\DataFixture;
use PHPUnit\Framework\TestCase;

#[
    AppArea('frontend'),
    DataFixture(ProductFixture::class, ['price' => 10], as: 'product'),
    DataFixture(GuestCartFixture::class, as: 'cart'),
    DataFixture(SetGuestEmailFixture::class, ['cart_id' => '$cart.id$']),
    DataFixture(AddProductToCartFixture::class, ['cart_id' => '$cart.id$', 'product_id' => '$product.id$', 'qty' => 1]),
    DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart.id$']),
    DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart.id$']),
    DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$cart.id$']),
]
class InstoreComponentsTest extends TestCase
{
    use PayNlTestTrait;

    private const PINMOMENT = 'loki-checkout.payment.methods.paynl_payment_instore.form.pinmoment';
    private const TERMINAL = 'loki-checkout.payment.methods.paynl_payment_instore.form.payment_option';
    private const TERMINALS = '[{"code":"TH-1","name":"Terminal 1"},{"code":"TH-2","name":"Terminal 2"}]';

    protected function setUp(): void
    {
        $this->resetPayNlServices();
        $this->setStoreConfigValue('payment/paynl_payment_instore/active', '1');
        $this->setStoreConfigValue('payment/paynl_payment_instore/pinmoment', '0');
        $this->setStoreConfigValue('payment/paynl_payment_instore/default_terminal', '0');
        $this->setStoreConfigValue('payment/paynl_payment_instore/pinmoment_terminal', '0');
        $this->setStoreConfigValue('payment/paynl_payment_instore/hide_terminal_selection', '0');
        $this->setDefaultConfigValue('payment/paynl/terminals', self::TERMINALS);
    }

    protected function tearDown(): void
    {
        $this->restoreConfig();
        $this->resetPayNlServices();
    }

    public function testComponentClasses(): void
    {
        $this->initInstoreCart();

        $pinMoment = $this->getComponentByName(self::PINMOMENT);
        $this->assertInstanceOf(PinMomentViewModel::class, $pinMoment->getViewModel());
        $this->assertInstanceOf(PinMomentRepository::class, $pinMoment->getRepository());

        $terminal = $this->getComponentByName(self::TERMINAL);
        $this->assertInstanceOf(TerminalViewModel::class, $terminal->getViewModel());
        $this->assertInstanceOf(TerminalRepository::class, $terminal->getRepository());
        $this->assertSame('paynl_instore_terminal', $terminal->getViewModel()->getFieldName());
        $this->assertSame('Select terminal', $terminal->getViewModel()->getFieldLabel());
    }

    public function testPinMomentChoiceIsShownForPickupWithChoiceConfig(): void
    {
        $this->setStoreConfigValue('payment/paynl_payment_instore/pinmoment', '2');
        $this->initInstoreCart('instore_pickup');
        $viewModel = $this->getPinMomentViewModel();

        $this->assertTrue($viewModel->isAllowRendering());
        $this->assertTrue($viewModel->isRequired());
        $options = $viewModel->getOptionModel()->getOptions($viewModel);
        $this->assertCount(2, $options);
    }

    public function testPinMomentChoiceIsHiddenWithoutPickupShipping(): void
    {
        $this->setStoreConfigValue('payment/paynl_payment_instore/pinmoment', '2');
        $this->initInstoreCart('flatrate_flatrate');

        $this->assertFalse($this->getPinMomentViewModel()->isAllowRendering());
    }

    public function testPinMomentChoiceIsHiddenWithoutChoiceConfig(): void
    {
        foreach (['0', '1'] as $pinMoment) {
            $this->setStoreConfigValue('payment/paynl_payment_instore/pinmoment', $pinMoment);
            $this->initInstoreCart('instore_pickup');

            $this->assertFalse($this->getPinMomentViewModel()->isAllowRendering(), 'pinmoment ' . $pinMoment);
        }
    }

    public function testPinMomentDefaultsToCheckout(): void
    {
        $this->initInstoreCart();
        $repository = $this->getPinMomentRepository();

        $this->assertSame('pinmoment', $repository->getPropertyName());
        $this->assertSame('0', $repository->getDefaultValue());
        $this->assertSame('0', $repository->getValue());
    }

    public function testPinMomentPickupStoresPickupTerminal(): void
    {
        $this->setStoreConfigValue('payment/paynl_payment_instore/pinmoment_terminal', 'TH-9');
        $quote = $this->initInstoreCart('instore_pickup');

        $this->getPinMomentRepository()->saveValue('1');

        $payment = $this->reloadQuote($quote)->getPayment();
        $this->assertSame('1', $payment->getAdditionalInformation('pinmoment'));
        $this->assertSame('TH-9', $payment->getAdditionalInformation('payment_option'));
        $this->assertSame('1', $this->getPinMomentRepository()->getValue());
    }

    public function testPinMomentPickupWithoutPickupTerminalKeepsTerminal(): void
    {
        $quote = $this->initInstoreCart('instore_pickup');
        $this->getTerminalRepository()->saveValue('TH-2');

        $this->getPinMomentRepository()->saveValue('1');

        $payment = $this->reloadQuote($quote)->getPayment();
        $this->assertSame('1', $payment->getAdditionalInformation('pinmoment'));
        $this->assertSame('TH-2', $payment->getAdditionalInformation('payment_option'));
    }

    public function testPinMomentCheckoutDoesNotChangeTerminal(): void
    {
        $this->setStoreConfigValue('payment/paynl_payment_instore/pinmoment_terminal', 'TH-9');
        $quote = $this->initInstoreCart('instore_pickup');
        $this->getTerminalRepository()->saveValue('TH-1');

        $this->getPinMomentRepository()->saveValue('0');

        $payment = $this->reloadQuote($quote)->getPayment();
        $this->assertSame('0', $payment->getAdditionalInformation('pinmoment'));
        $this->assertSame('TH-1', $payment->getAdditionalInformation('payment_option'));
    }

    public function testInvalidPinMomentIsStoredAsCheckout(): void
    {
        $quote = $this->initInstoreCart();

        $this->getPinMomentRepository()->saveValue('foobar');

        $this->assertSame('0', $this->reloadQuote($quote)->getPayment()->getAdditionalInformation('pinmoment'));
    }

    public function testTerminalsAreListed(): void
    {
        $this->initInstoreCart();

        $this->assertSame([
            ['id' => 'TH-1', 'label' => 'Terminal 1'],
            ['id' => 'TH-2', 'label' => 'Terminal 2'],
        ], $this->getTerminalViewModel()->getTerminals());
    }

    public function testTerminalSelectionIsVisibleByDefault(): void
    {
        $this->initInstoreCart();
        $viewModel = $this->getTerminalViewModel();

        $this->assertTrue($viewModel->isTerminalSelectionVisible());
        $this->assertTrue($viewModel->isAllowRendering());
        $this->assertTrue($viewModel->isRequired());
    }

    public function testTerminalSelectionIsHiddenWithHideFlagAndDefaultTerminal(): void
    {
        $this->setStoreConfigValue('payment/paynl_payment_instore/hide_terminal_selection', '1');
        $this->setStoreConfigValue('payment/paynl_payment_instore/default_terminal', 'TH-1');
        $this->initInstoreCart();
        $viewModel = $this->getTerminalViewModel();

        $this->assertFalse($viewModel->isAllowRendering());
        $this->assertFalse($viewModel->isRequired());
    }

    public function testTerminalSelectionIsVisibleWithHideFlagWithoutDefaultTerminal(): void
    {
        $this->setStoreConfigValue('payment/paynl_payment_instore/hide_terminal_selection', '1');
        $this->initInstoreCart();

        $this->assertTrue($this->getTerminalViewModel()->isAllowRendering());
    }

    public function testTerminalSelectionIsHiddenWhenPayingAtPickupWithPickupTerminal(): void
    {
        $this->setStoreConfigValue('payment/paynl_payment_instore/pinmoment', '1');
        $this->setStoreConfigValue('payment/paynl_payment_instore/pinmoment_terminal', 'TH-9');
        $this->initInstoreCart('instore_pickup');

        $this->assertFalse($this->getTerminalViewModel()->isAllowRendering());
    }

    public function testTerminalSelectionIsVisibleWhenPayingAtPickupWithoutPickupTerminal(): void
    {
        $this->setStoreConfigValue('payment/paynl_payment_instore/pinmoment', '1');
        $this->initInstoreCart('instore_pickup');

        $this->assertTrue($this->getTerminalViewModel()->isAllowRendering());
    }

    public function testTerminalSelectionFollowsPinMomentChoice(): void
    {
        $this->setStoreConfigValue('payment/paynl_payment_instore/pinmoment', '2');
        $this->setStoreConfigValue('payment/paynl_payment_instore/pinmoment_terminal', 'TH-9');
        $quote = $this->initInstoreCart('instore_pickup');

        $quote->getPayment()->setAdditionalInformation('pinmoment', '0');
        $this->assertTrue($this->getTerminalViewModel()->isAllowRendering());

        $quote->getPayment()->setAdditionalInformation('pinmoment', '1');
        $this->assertFalse($this->getTerminalViewModel()->isAllowRendering());
    }

    public function testValidTerminalIsStored(): void
    {
        $quote = $this->initInstoreCart();
        $repository = $this->getTerminalRepository();

        $repository->saveValue('TH-2');

        $this->assertSame('payment_option', $repository->getPropertyName());
        $this->assertSame('TH-2', $this->reloadQuote($quote)->getPayment()->getAdditionalInformation('payment_option'));
        $this->assertSame('TH-2', $repository->getValue());
    }

    public function testInvalidTerminalIsRejected(): void
    {
        $quote = $this->initInstoreCart();
        $component = $this->getComponentByName(self::TERMINAL);

        $component->getRepository()->saveValue('TH-99');

        $this->assertNull($this->reloadQuote($quote)->getPayment()->getAdditionalInformation('payment_option'));
        $this->assertSame(['Please select a valid terminal'], $this->getErrors($component));
    }

    public function testEmptyTerminalIsNotStoredWithoutError(): void
    {
        $quote = $this->initInstoreCart();
        $component = $this->getComponentByName(self::TERMINAL);

        $component->getRepository()->saveValue('');

        $this->assertNull($this->reloadQuote($quote)->getPayment()->getAdditionalInformation('payment_option'));
        $this->assertSame([], $this->getErrors($component));
    }

    public function testConfiguredTerminalsAreValidWithoutTerminalList(): void
    {
        $this->setDefaultConfigValue('payment/paynl/terminals', '');
        $this->setStoreConfigValue('payment/paynl_payment_instore/default_terminal', 'TH-5');
        $this->setStoreConfigValue('payment/paynl_payment_instore/pinmoment_terminal', 'TH-6');
        $quote = $this->initInstoreCart();

        $this->getTerminalRepository()->saveValue('TH-6');
        $this->assertSame('TH-6', $this->reloadQuote($quote)->getPayment()->getAdditionalInformation('payment_option'));

        $this->getTerminalRepository()->saveValue('0');
        $this->assertSame('TH-6', $this->reloadQuote($quote)->getPayment()->getAdditionalInformation('payment_option'));
    }

    public function testDefaultTerminalIsUsedAsValue(): void
    {
        $this->setStoreConfigValue('payment/paynl_payment_instore/default_terminal', 'TH-2');
        $quote = $this->initInstoreCart();
        $repository = $this->getTerminalRepository();

        $this->assertSame('TH-2', $repository->getDefaultValue());
        $this->assertSame('TH-2', $repository->getValue());
        $this->assertSame('TH-2', $this->reloadQuote($quote)->getPayment()->getAdditionalInformation('payment_option'));
    }

    public function testOnlyTerminalIsUsedAsValue(): void
    {
        $this->setDefaultConfigValue('payment/paynl/terminals', '[{"code":"TH-1","name":"Terminal 1"}]');
        $this->initInstoreCart();

        $this->assertSame('TH-1', $this->getTerminalRepository()->getValue());
    }

    public function testNoValueWithMultipleTerminalsAndNoDefault(): void
    {
        $this->initInstoreCart();
        $repository = $this->getTerminalRepository();

        $this->assertSame('', $repository->getDefaultValue());
        $this->assertNull($repository->getValue());
    }

    public function testStoredTerminalWinsOverDefault(): void
    {
        $this->setStoreConfigValue('payment/paynl_payment_instore/default_terminal', 'TH-2');
        $this->initInstoreCart();
        $repository = $this->getTerminalRepository();

        $repository->saveValue('TH-1');

        $this->assertSame('TH-1', $repository->getValue());
    }

    private function initInstoreCart(string $shippingMethod = 'flatrate_flatrate'): Quote
    {
        $quote = $this->initCart('paynl_payment_instore');
        $quote->getShippingAddress()->setShippingMethod($shippingMethod);

        return $quote;
    }

    private function getErrors(ComponentInterface $component): array
    {
        $errors = [];
        foreach ($component->getLocalMessageRegistry()->getMessages() as $message) {
            if ($message->getType() === 'error') {
                $errors[] = $message->getText();
            }
        }

        return $errors;
    }

    private function getPinMomentViewModel(): PinMomentViewModel
    {
        $viewModel = $this->getComponentByName(self::PINMOMENT)->getViewModel();
        $this->assertInstanceOf(PinMomentViewModel::class, $viewModel);

        return $viewModel;
    }

    private function getPinMomentRepository(): PinMomentRepository
    {
        $repository = $this->getComponentByName(self::PINMOMENT)->getRepository();
        $this->assertInstanceOf(PinMomentRepository::class, $repository);

        return $repository;
    }

    private function getTerminalViewModel(): TerminalViewModel
    {
        $viewModel = $this->getComponentByName(self::TERMINAL)->getViewModel();
        $this->assertInstanceOf(TerminalViewModel::class, $viewModel);

        return $viewModel;
    }

    private function getTerminalRepository(): TerminalRepository
    {
        $repository = $this->getComponentByName(self::TERMINAL)->getRepository();
        $this->assertInstanceOf(TerminalRepository::class, $repository);

        return $repository;
    }
}
