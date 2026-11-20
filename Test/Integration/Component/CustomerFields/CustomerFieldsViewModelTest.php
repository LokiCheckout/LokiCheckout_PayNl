<?php

declare(strict_types=1);

namespace LokiCheckout\PayNl\Test\Integration\Component\CustomerFields;

use LokiCheckout\PayNl\Component\Checkout\Payment\Method\CustomerFields\CustomerFieldsRepository;
use LokiCheckout\PayNl\Component\Checkout\Payment\Method\CustomerFields\CustomerFieldsViewModel;
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
class CustomerFieldsViewModelTest extends TestCase
{
    use PayNlTestTrait;

    protected function tearDown(): void
    {
        $this->restoreConfig();
    }

    public function testComponentsUseCustomerFieldsClasses(): void
    {
        $this->initCart('paynl_payment_billink');
        $component = $this->getComponentByName('loki-checkout.payment.methods.paynl_payment_billink.form.dob');

        $this->assertInstanceOf(CustomerFieldsViewModel::class, $component->getViewModel());
        $this->assertInstanceOf(CustomerFieldsRepository::class, $component->getRepository());
    }

    public function testMethodCodeAndPropertyAreReadFromComponentName(): void
    {
        $this->initCart('paynl_payment_afterpay_international');
        $viewModel = $this->getViewModel('paynl_payment_afterpay_international', 'cocnumber');

        $this->assertSame('paynl_payment_afterpay_international', $viewModel->getMethodCode());
        $this->assertSame('cocnumber', $viewModel->getPropertyName());
    }

    public static function getModeCases(): array
    {
        return [
            'hidden' => ['0', false, false],
            'optional' => ['1', true, false],
            'required' => ['2', true, true],
            'from checkout' => ['3', false, false],
            'not configured' => ['', false, false],
        ];
    }

    #[DataProvider('getModeCases')]
    public function testDobMode(string $mode, bool $visible, bool $required): void
    {
        $this->setStoreConfigValue('payment/paynl_payment_klarna/showdob', $mode);
        $this->initCart('paynl_payment_klarna');
        $viewModel = $this->getViewModel('paynl_payment_klarna', 'dob');

        $this->assertSame($visible, $viewModel->isFieldVisible());
        $this->assertSame($visible, $viewModel->isAllowRendering());
        $this->assertSame($required, $viewModel->isRequired());
    }

    #[DataProvider('getModeCases')]
    public function testCocNumberMode(string $mode, bool $visible, bool $required): void
    {
        $this->setStoreConfigValue('payment/paynl_payment_spraypay/showkvk', $mode);
        $this->initCart('paynl_payment_spraypay');
        $viewModel = $this->getViewModel('paynl_payment_spraypay', 'cocnumber');

        $this->assertSame($visible, $viewModel->isAllowRendering());
        $this->assertSame($required, $viewModel->isRequired());
    }

    #[DataProvider('getModeCases')]
    public function testVatNumberModeWithBillingCompany(string $mode, bool $visible, bool $required): void
    {
        $this->setStoreConfigValue('payment/paynl_payment_in3business/showvat', $mode);
        $this->initCart('paynl_payment_in3business', 'Yireo');
        $viewModel = $this->getViewModel('paynl_payment_in3business', 'vatnumber');

        $this->assertSame($visible, $viewModel->isAllowRendering());
        $this->assertSame($required, $viewModel->isRequired());
    }

    public function testVatNumberIsHiddenWithoutCompany(): void
    {
        $this->setStoreConfigValue('payment/paynl_payment_in3business/showvat', '2');
        $this->setStoreConfigValue('payment/paynl_payment_in3business/showcompanyfield', '0');
        $this->initCart('paynl_payment_in3business');

        $this->assertFalse($this->getViewModel('paynl_payment_in3business', 'vatnumber')->isAllowRendering());
    }

    public function testVatNumberIsShownWithCompanyField(): void
    {
        $this->setStoreConfigValue('payment/paynl_payment_biller/showvat', '1');
        $this->setStoreConfigValue('payment/paynl_payment_biller/showcompanyfield', '1');
        $this->initCart('paynl_payment_biller');

        $this->assertTrue($this->getViewModel('paynl_payment_biller', 'vatnumber')->isAllowRendering());
        $this->assertTrue($this->getViewModel('paynl_payment_biller', 'companyfield')->isAllowRendering());
    }

    public function testCompanyFieldIsHiddenWithBillingCompany(): void
    {
        $this->setStoreConfigValue('payment/paynl_payment_biller/showvat', '1');
        $this->setStoreConfigValue('payment/paynl_payment_biller/showcompanyfield', '2');
        $this->initCart('paynl_payment_biller', 'Yireo');

        $this->assertFalse($this->getViewModel('paynl_payment_biller', 'companyfield')->isAllowRendering());
        $this->assertTrue($this->getViewModel('paynl_payment_biller', 'vatnumber')->isAllowRendering());
    }

    #[DataProvider('getModeCases')]
    public function testCompanyFieldMode(string $mode, bool $visible, bool $required): void
    {
        $this->setStoreConfigValue('payment/paynl_payment_biller/showcompanyfield', $mode);
        $this->initCart('paynl_payment_biller');
        $viewModel = $this->getViewModel('paynl_payment_biller', 'companyfield');

        $this->assertSame($visible, $viewModel->isAllowRendering());
        $this->assertSame($required, $viewModel->isRequired());
    }

    public static function getBillinkAgreeCases(): array
    {
        return [
            'coc hidden' => ['0', false],
            'coc optional' => ['1', true],
            'coc required' => ['2', true],
            'coc from checkout' => ['3', true],
        ];
    }

    #[DataProvider('getBillinkAgreeCases')]
    public function testBillinkAgreeFollowsCocNumberSetting(string $showKvk, bool $visible): void
    {
        $this->setStoreConfigValue('payment/paynl_payment_billink/showkvk', $showKvk);
        $this->initCart('paynl_payment_billink');
        $viewModel = $this->getViewModel('paynl_payment_billink', 'billink_agree');

        $this->assertSame($visible, $viewModel->isAllowRendering());
        $this->assertTrue($viewModel->isRequired());
    }

    public function testUnknownFieldIsHidden(): void
    {
        $this->initCart('paynl_payment_billink');

        $this->assertSame(CustomerFieldsViewModel::MODE_HIDDEN, $this->getViewModel('paynl_payment_billink', 'dob')->getFieldMode('foobar'));
    }

    public function testSettingsArePerMethod(): void
    {
        $this->setStoreConfigValue('payment/paynl_payment_afterpay/showdob', '2');
        $this->setStoreConfigValue('payment/paynl_payment_yourgift/showdob', '0');
        $this->initCart('paynl_payment_afterpay');

        $this->assertTrue($this->getViewModel('paynl_payment_afterpay', 'dob')->isAllowRendering());
        $this->assertFalse($this->getViewModel('paynl_payment_yourgift', 'dob')->isAllowRendering());
    }

    private function getViewModel(string $methodCode, string $fieldName): CustomerFieldsViewModel
    {
        $component = $this->getComponentByName('loki-checkout.payment.methods.' . $methodCode . '.form.' . $fieldName);
        $viewModel = $component->getViewModel();
        $this->assertInstanceOf(CustomerFieldsViewModel::class, $viewModel);

        return $viewModel;
    }
}
