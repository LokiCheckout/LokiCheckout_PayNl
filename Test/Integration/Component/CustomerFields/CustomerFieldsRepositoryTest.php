<?php

declare(strict_types=1);

namespace LokiCheckout\PayNl\Test\Integration\Component\CustomerFields;

use LokiCheckout\Core\ViewModel\CheckoutState;
use LokiCheckout\PayNl\Component\Checkout\Payment\Method\CustomerFields\CustomerFieldsRepository;
use LokiCheckout\PayNl\Test\Integration\Traits\PayNlTestTrait;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Checkout\Test\Fixture\SetBillingAddress as SetBillingAddressFixture;
use Magento\Checkout\Test\Fixture\SetDeliveryMethod as SetDeliveryMethodFixture;
use Magento\Checkout\Test\Fixture\SetGuestEmail as SetGuestEmailFixture;
use Magento\Checkout\Test\Fixture\SetShippingAddress as SetShippingAddressFixture;
use Magento\Quote\Test\Fixture\AddProductToCart as AddProductToCartFixture;
use Magento\Quote\Test\Fixture\GuestCart as GuestCartFixture;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\DataFixture;
use PHPUnit\Framework\Attributes\DataProvider;
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
class CustomerFieldsRepositoryTest extends TestCase
{
    use PayNlTestTrait;

    public function testDobIsStoredInPayNlFormat(): void
    {
        $quote = $this->initCart('paynl_payment_billink');
        $repository = $this->getRepository('paynl_payment_billink', 'dob');

        $repository->saveValue('1980-01-31');

        $this->assertSame('31-01-1980', $this->reloadQuote($quote)->getPayment()->getAdditionalInformation('dob'));
        $this->assertSame('1980-01-31', $repository->getValue());
    }

    public function testInvalidDobIsStoredEmpty(): void
    {
        $quote = $this->initCart('paynl_payment_billink');
        $repository = $this->getRepository('paynl_payment_billink', 'dob');

        $repository->saveValue('1980-01-31');
        $repository->saveValue('foobar');

        $this->assertSame('', $this->reloadQuote($quote)->getPayment()->getAdditionalInformation('dob'));
        $this->assertSame('', $repository->getValue());
    }

    public static function getCustomerDobCases(): array
    {
        return [
            'date' => ['1975-12-01', '1975-12-01'],
            'datetime' => ['1975-12-01 00:00:00', '1975-12-01'],
            'none' => [null, ''],
        ];
    }

    #[DataProvider('getCustomerDobCases')]
    public function testDobDefaultsToCustomerDob(?string $customerDob, string $expected): void
    {
        $this->initCart('paynl_payment_afterpay');
        $this->getTestObjectManager()->get(CheckoutState::class)->getQuote()->setCustomerDob($customerDob);
        $repository = $this->getRepository('paynl_payment_afterpay', 'dob');

        $this->assertSame($expected, $repository->getDefaultValue());
        $this->assertSame($expected, $repository->getValue());
    }

    public function testStoredDobWinsOverCustomerDob(): void
    {
        $this->initCart('paynl_payment_afterpay');
        $repository = $this->getRepository('paynl_payment_afterpay', 'dob');
        $repository->saveValue('1980-01-31');
        $this->getTestObjectManager()->get(CheckoutState::class)->getQuote()->setCustomerDob('1975-12-01');

        $this->assertSame('1980-01-31', $repository->getValue());
    }

    public function testTextFieldsAreTrimmed(): void
    {
        $quote = $this->initCart('paynl_payment_biller');
        $this->getRepository('paynl_payment_biller', 'cocnumber')->saveValue(' 12345678 ');
        $this->getRepository('paynl_payment_biller', 'vatnumber')->saveValue('NL123456789B01');
        $this->getRepository('paynl_payment_biller', 'companyfield')->saveValue('Yireo');

        $payment = $this->reloadQuote($quote)->getPayment();
        $this->assertSame('12345678', $payment->getAdditionalInformation('cocnumber'));
        $this->assertSame('NL123456789B01', $payment->getAdditionalInformation('vatnumber'));
        $this->assertSame('Yireo', $payment->getAdditionalInformation('companyfield'));
        $this->assertSame('12345678', $this->getRepository('paynl_payment_biller', 'cocnumber')->getValue());
        $this->assertSame('', $this->getRepository('paynl_payment_biller', 'dob')->getDefaultValue());
        $this->assertSame('', $this->getRepository('paynl_payment_biller', 'cocnumber')->getDefaultValue());
    }

    public static function getBillinkAgreeCases(): array
    {
        return [
            'true' => [true, '1', true],
            'false' => [false, '0', false],
            'string 1' => ['1', '1', true],
            'string on' => ['on', '1', true],
            'string true' => ['true', '1', true],
            'string 0' => ['0', '0', false],
            'empty' => ['', '0', false],
            'int 1' => [1, '1', true],
            'int 0' => [0, '0', false],
            'null' => [null, '0', false],
        ];
    }

    #[DataProvider('getBillinkAgreeCases')]
    public function testBillinkAgreeIsStoredAsZeroOrOne(mixed $value, string $stored, bool $expected): void
    {
        $quote = $this->initCart('paynl_payment_billink');
        $repository = $this->getRepository('paynl_payment_billink', 'billink_agree');

        $repository->saveValue($value);

        $this->assertSame($stored, $this->reloadQuote($quote)->getPayment()->getAdditionalInformation('billink_agree'));
        $this->assertSame($expected, $repository->getValue());
    }

    public function testBillinkAgreeDefaultsToUnchecked(): void
    {
        $this->initCart('paynl_payment_billink');
        $repository = $this->getRepository('paynl_payment_billink', 'billink_agree');

        $this->assertFalse($repository->getDefaultValue());
        $this->assertFalse($repository->getValue());
    }

    private function getRepository(string $methodCode, string $fieldName): CustomerFieldsRepository
    {
        $component = $this->getComponentByName('loki-checkout.payment.methods.' . $methodCode . '.form.' . $fieldName);
        $repository = $component->getRepository();
        $this->assertInstanceOf(CustomerFieldsRepository::class, $repository);

        return $repository;
    }
}
