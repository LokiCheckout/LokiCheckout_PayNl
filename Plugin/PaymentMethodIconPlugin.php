<?php declare(strict_types=1);

namespace Yireo\LokiCheckoutPayNl\Plugin;

use Paynl\Payment\Model\Config;
use Yireo\LokiCheckout\ViewModel\PaymentMethodIcon;

class PaymentMethodIconPlugin
{
    public function __construct(
        private Config $config
    ) {
    }

    public function afterGetIcon(
        PaymentMethodIcon $paymentMethodIcon,
        string $result,
        string $paymentMethodCode
    ): string {
        if (false === $paymentMethodIcon->isModuleEnabled('Paynl_Payment')) {
            return $result;
        }

        if (!preg_match('/^paynl_(.*)$/', $paymentMethodCode)) {
            return $result;
        }

        $iconUrl = $this->config->getIconUrl($paymentMethodCode);

        return '<img src="' . $iconUrl . '" />';
    }
}
