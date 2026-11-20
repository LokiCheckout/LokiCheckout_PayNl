<?php declare(strict_types=1);

namespace LokiCheckout\PayNl\Component\Checkout\Payment\Method\CustomerFields;

use LokiCheckout\Core\Component\Base\Generic\CheckoutContext;
use LokiCheckout\Core\Component\Base\Payment\AdditionalInformation\AdditionalInformationRepository;
use LokiCheckout\PayNl\Util\DateOfBirth;

/**
 * @method CheckoutContext getContext()
 */
class CustomerFieldsRepository extends AdditionalInformationRepository
{
    public function __construct(
        private readonly DateOfBirth $dateOfBirth,
    ) {
    }

    public function getValue(): mixed
    {
        $propertyName = $this->getPropertyName();
        $value = $this->getPropertyValue('');

        if ($propertyName === CustomerFieldsViewModel::FIELD_BILLINK_AGREE) {
            return (int)$value === 1;
        }

        $value = is_scalar($value) ? trim((string)$value, " \f\n\r\t\v\x00") : '';

        if ($propertyName === CustomerFieldsViewModel::FIELD_DOB) {
            if ($value === '') {
                $value = (string)$this->getDefaultValue();
            }

            return $this->dateOfBirth->toInputFormat($value);
        }

        return $value;
    }

    public function getDefaultValue(): mixed
    {
        if ($this->getPropertyName() === CustomerFieldsViewModel::FIELD_DOB) {
            $customerDob = (string)$this->getContext()->getCheckoutState()->getQuote()->getCustomerDob();

            return $this->dateOfBirth->toInputFormat($customerDob);
        }

        if ($this->getPropertyName() === CustomerFieldsViewModel::FIELD_BILLINK_AGREE) {
            return false;
        }

        return '';
    }

    public function saveValue(mixed $value): void
    {
        $propertyName = $this->getPropertyName();

        if ($propertyName === CustomerFieldsViewModel::FIELD_BILLINK_AGREE) {
            $this->saveProperty($this->isChecked($value) ? '1' : '0');
            return;
        }

        $value = is_scalar($value) ? trim((string)$value, " \f\n\r\t\v\x00") : '';

        if ($propertyName === CustomerFieldsViewModel::FIELD_DOB) {
            $this->saveProperty($this->dateOfBirth->toPayNlFormat($value));
            return;
        }

        $this->saveProperty($value);
    }

    private function isChecked(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return $value > 0;
        }

        if (is_string($value)) {
            return in_array(strtolower(trim($value, " \f\n\r\t\v\x00")), ['1', 'true', 'on', 'yes'], true);
        }

        return false;
    }
}
