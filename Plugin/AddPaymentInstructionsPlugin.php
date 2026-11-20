<?php declare(strict_types=1);

namespace LokiCheckout\PayNl\Plugin;

use LokiCheckout\Core\Component\Checkout\Billing\PaymentMethods\PaymentMethodsViewModel;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class AddPaymentInstructionsPlugin
{
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
    ) {
    }

    public function afterGetPaymentInstructions(
        PaymentMethodsViewModel $subject,
        string $result,
        string $paymentCode
    ): string {
        if ($result !== '') {
            return $result;
        }

        if (false === str_starts_with($paymentCode, 'paynl_')) {
            return $result;
        }

        return (string)$this->scopeConfig->getValue(
            'payment/' . $paymentCode . '/instructions',
            ScopeInterface::SCOPE_STORE
        );
    }
}
