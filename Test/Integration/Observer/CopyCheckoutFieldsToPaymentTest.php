<?php

declare(strict_types=1);

namespace LokiCheckout\PayNl\Test\Integration\Observer;

use LokiCheckout\PayNl\Observer\CopyCheckoutFieldsToPayment;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event;
use Magento\Framework\Event\ConfigInterface as EventConfig;
use Magento\Framework\Event\Observer;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use PHPUnit\Framework\TestCase;

class CopyCheckoutFieldsToPaymentTest extends TestCase
{
    public function testObserverIsRegistered(): void
    {
        $observers = ObjectManager::getInstance()->get(EventConfig::class)
            ->getObservers('sales_model_service_quote_submit_before');

        $this->assertArrayHasKey('LokiCheckout_PayNl::copyCheckoutFieldsToPayment', $observers);
        $this->assertSame(
            CopyCheckoutFieldsToPayment::class,
            ltrim($observers['LokiCheckout_PayNl::copyCheckoutFieldsToPayment']['instance'], '\\')
        );
    }

    public function testBillingAttributesAreCopied(): void
    {
        $quote = $this->createQuote('paynl_payment_afterpay');
        $billingAddress = $quote->getBillingAddress();
        $billingAddress->setData('paynl_dob', '1980-01-31');
        $billingAddress->setData('paynl_coc_number', '12345678');
        $billingAddress->setData('paynl_vat_number', 'NL123456789B01');
        $billingAddress->setVatId('NL000000000B00');
        $quote->setCustomerDob('1975-12-01');
        $order = $this->createOrder();

        $this->execute($quote, $order);

        foreach ([$quote->getPayment(), $order->getPayment()] as $payment) {
            $this->assertSame('31-01-1980', $payment->getAdditionalInformation('dob'));
            $this->assertSame('12345678', $payment->getAdditionalInformation('cocnumber'));
            $this->assertSame('NL123456789B01', $payment->getAdditionalInformation('vatnumber'));
        }
    }

    public function testFallbacksAreUsed(): void
    {
        $quote = $this->createQuote('paynl_payment_billink');
        $quote->getBillingAddress()->setVatId(' NL000000000B00 ');
        $quote->setCustomerDob('1975-12-01 00:00:00');
        $order = $this->createOrder();

        $this->execute($quote, $order);

        $payment = $order->getPayment();
        $this->assertSame('01-12-1975', $payment->getAdditionalInformation('dob'));
        $this->assertSame('NL000000000B00', $payment->getAdditionalInformation('vatnumber'));
        $this->assertNull($payment->getAdditionalInformation('cocnumber'));
    }

    public function testExistingValuesAreKept(): void
    {
        $quote = $this->createQuote('paynl_payment_billink');
        $quote->getPayment()->setAdditionalInformation('dob', '02-02-1990');
        $quote->getPayment()->setAdditionalInformation('cocnumber', '87654321');
        $quote->getBillingAddress()->setData('paynl_coc_number', '12345678');
        $quote->setCustomerDob('1975-12-01');
        $order = $this->createOrder();
        $order->getPayment()->setAdditionalInformation('dob', '03-03-1991');

        $this->execute($quote, $order);

        $this->assertSame('02-02-1990', $quote->getPayment()->getAdditionalInformation('dob'));
        $this->assertSame('87654321', $quote->getPayment()->getAdditionalInformation('cocnumber'));
        $this->assertSame('03-03-1991', $order->getPayment()->getAdditionalInformation('dob'));
        $this->assertSame('12345678', $order->getPayment()->getAdditionalInformation('cocnumber'));
    }

    public function testOtherMethodsAreIgnored(): void
    {
        $quote = $this->createQuote('checkmo');
        $quote->getBillingAddress()->setData('paynl_coc_number', '12345678');
        $quote->setCustomerDob('1975-12-01');
        $order = $this->createOrder();

        $this->execute($quote, $order);

        $this->assertNull($quote->getPayment()->getAdditionalInformation('dob'));
        $this->assertNull($order->getPayment()->getAdditionalInformation('cocnumber'));
    }

    public function testWorksWithoutOrder(): void
    {
        $quote = $this->createQuote('paynl_payment_billink');
        $quote->setCustomerDob('1975-12-01');

        $this->execute($quote, null);

        $this->assertSame('01-12-1975', $quote->getPayment()->getAdditionalInformation('dob'));
    }

    public function testGetCheckoutValuesWithoutData(): void
    {
        $values = $this->getObserver()->getCheckoutValues($this->createQuote('paynl_payment_billink'));

        $this->assertSame(['dob' => '', 'cocnumber' => '', 'vatnumber' => ''], $values);
    }

    private function execute(Quote $quote, ?Order $order): void
    {
        $event = new Event(['quote' => $quote, 'order' => $order]);
        $this->getObserver()->execute(new Observer(['event' => $event]));
    }

    private function getObserver(): CopyCheckoutFieldsToPayment
    {
        return ObjectManager::getInstance()->create(CopyCheckoutFieldsToPayment::class);
    }

    private function createQuote(string $paymentMethod): Quote
    {
        $quote = ObjectManager::getInstance()->create(Quote::class);
        $quote->getPayment()->setMethod($paymentMethod);

        return $quote;
    }

    private function createOrder(): Order
    {
        $order = ObjectManager::getInstance()->create(Order::class);
        $order->setPayment(ObjectManager::getInstance()->create(OrderPayment::class));

        return $order;
    }
}
