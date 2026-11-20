<?php declare(strict_types=1);

namespace LokiCheckout\PayNl\Component\Checkout\Payment\Method\CustomerFields;

use LokiCheckout\Core\Component\Base\Generic\CheckoutContext;
use LokiCheckout\Core\Component\Base\Payment\AdditionalInformation\AdditionalInformationViewModel;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * @method CheckoutContext getContext()
 */
class CustomerFieldsViewModel extends AdditionalInformationViewModel
{
    public const MODE_HIDDEN = 0;
    public const MODE_OPTIONAL = 1;
    public const MODE_REQUIRED = 2;
    public const MODE_FROM_CHECKOUT = 3;

    public const FIELD_DOB = 'dob';
    public const FIELD_COC_NUMBER = 'cocnumber';
    public const FIELD_VAT_NUMBER = 'vatnumber';
    public const FIELD_COMPANY = 'companyfield';
    public const FIELD_BILLINK_AGREE = 'billink_agree';

    private const CONFIG_KEYS = [
        self::FIELD_DOB => 'showdob',
        self::FIELD_COC_NUMBER => 'showkvk',
        self::FIELD_VAT_NUMBER => 'showvat',
        self::FIELD_COMPANY => 'showcompanyfield',
    ];

    public function isAllowRendering(): bool
    {
        if (false === $this->isFieldVisible()) {
            return false;
        }

        return parent::isAllowRendering();
    }

    public function isRequired(): bool
    {
        $fieldName = $this->getPropertyName();
        if ($fieldName === self::FIELD_BILLINK_AGREE) {
            return true;
        }

        return $this->getFieldMode($fieldName) === self::MODE_REQUIRED;
    }

    public function isFieldVisible(): bool
    {
        $fieldName = $this->getPropertyName();

        if ($fieldName === self::FIELD_BILLINK_AGREE) {
            return $this->getFieldMode(self::FIELD_COC_NUMBER) > self::MODE_HIDDEN;
        }

        if (false === $this->isModeVisible($this->getFieldMode($fieldName))) {
            return false;
        }

        if ($fieldName === self::FIELD_COMPANY) {
            return false === $this->hasBillingCompany();
        }

        if ($fieldName === self::FIELD_VAT_NUMBER) {
            return $this->hasBillingCompany() || $this->isCompanyFieldVisible();
        }

        return true;
    }

    public function getFieldMode(string $fieldName): int
    {
        if (false === array_key_exists($fieldName, self::CONFIG_KEYS)) {
            return self::MODE_HIDDEN;
        }

        $methodCode = $this->getMethodCode();
        if ($methodCode === '') {
            return self::MODE_HIDDEN;
        }

        return (int)$this->getScopeConfig()->getValue(
            'payment/' . $methodCode . '/' . self::CONFIG_KEYS[$fieldName],
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getMethodCode(): string
    {
        if (preg_match('/^loki-checkout\.payment\.methods\.([^.]+)\.form\./', $this->getComponentName(), $match)) {
            return $match[1];
        }

        return '';
    }

    public function getPropertyName(): string
    {
        $parts = explode('.', $this->getComponentName());

        return (string)end($parts);
    }

    private function isCompanyFieldVisible(): bool
    {
        if (false === $this->isModeVisible($this->getFieldMode(self::FIELD_COMPANY))) {
            return false;
        }

        return false === $this->hasBillingCompany();
    }

    private function isModeVisible(int $mode): bool
    {
        return in_array($mode, [self::MODE_OPTIONAL, self::MODE_REQUIRED], true);
    }

    private function hasBillingCompany(): bool
    {
        $billingAddress = $this->getContext()->getCheckoutState()->getQuote()->getBillingAddress();

        return trim((string)$billingAddress->getCompany(), " \f\n\r\t\v\x00") !== '';
    }

    private function getScopeConfig(): ScopeConfigInterface
    {
        return $this->getContext()->getScopeConfig();
    }
}
