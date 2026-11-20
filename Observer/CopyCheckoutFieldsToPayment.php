<?php declare(strict_types=1);

namespace LokiCheckout\PayNl\Observer;

use LokiCheckout\PayNl\Util\DateOfBirth;
use Magento\Framework\Api\AttributeInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Sales\Model\Order;

class CopyCheckoutFieldsToPayment implements ObserverInterface
{
    public const KEY_DOB = 'dob';
    public const KEY_COC_NUMBER = 'cocnumber';
    public const KEY_VAT_NUMBER = 'vatnumber';

    public function __construct(
        private readonly DateOfBirth $dateOfBirth,
    ) {
    }

    public function execute(Observer $observer): void
    {
        $quote = $observer->getEvent()->getData('quote');
        if (false === $quote instanceof Quote) {
            return;
        }

        $quotePayment = $quote->getPayment();
        if (false === str_starts_with((string)$quotePayment->getMethod(), 'paynl_')) {
            return;
        }

        $order = $observer->getEvent()->getData('order');
        $orderPayment = $order instanceof Order ? $order->getPayment() : null;

        foreach ($this->getCheckoutValues($quote) as $key => $value) {
            if ($value === '') {
                continue;
            }

            if ($this->isEmpty($quotePayment->getAdditionalInformation($key))) {
                $quotePayment->setAdditionalInformation($key, $value);
            }

            if ($orderPayment !== null && $this->isEmpty($orderPayment->getAdditionalInformation($key))) {
                $orderPayment->setAdditionalInformation($key, $value);
            }
        }
    }

    /**
     * @return array<string, string>
     */
    public function getCheckoutValues(Quote $quote): array
    {
        $billingAddress = $quote->getBillingAddress();

        $dob = $this->getAddressAttribute($billingAddress, 'paynl_dob');
        if ($dob === '') {
            $dob = (string)$quote->getCustomerDob();
        }

        $vatNumber = $this->getAddressAttribute($billingAddress, 'paynl_vat_number');
        if ($vatNumber === '') {
            $vatNumber = trim((string)$billingAddress->getVatId());
        }

        return [
            self::KEY_DOB => $this->dateOfBirth->toPayNlFormat($dob),
            self::KEY_COC_NUMBER => $this->getAddressAttribute($billingAddress, 'paynl_coc_number'),
            self::KEY_VAT_NUMBER => $vatNumber,
        ];
    }

    private function getAddressAttribute(Address $address, string $attributeCode): string
    {
        $attribute = $address->getCustomAttribute($attributeCode);
        if ($attribute instanceof AttributeInterface) {
            $value = $this->toString($attribute->getValue());
            if ($value !== '') {
                return $value;
            }
        }

        return $this->toString($address->getData($attributeCode));
    }

    private function toString(mixed $value): string
    {
        return is_scalar($value) ? trim((string)$value) : '';
    }

    private function isEmpty(mixed $value): bool
    {
        return $this->toString($value) === '';
    }
}
