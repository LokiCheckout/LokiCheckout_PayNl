<?php declare(strict_types=1);

namespace Yireo\LokiCheckoutPayNl\Plugin;

use Yireo\LokiCheckout\ViewModel\PaymentMethodIcon;

class PaymentMethodIconPlugin
{
    public function afterGetIcon(
        PaymentMethodIcon $paymentMethodIcon,
        string $result,
        string $paymentMethodCode
    ): string {
        if (false === $paymentMethodIcon->isModuleEnabled('Paynl_Payment')) {
            return $result;
        }

        if (!preg_match('/^paynl_(.*)$/', $paymentMethodCode, $match)) {
            return $result;
        }

        $iconFilePath = $paymentMethodIcon->getIconPath(
            'Paynl_Payment',
            'view/frontend/web/logos/'.$match[1].'.svg'
        );

        return $paymentMethodIcon->getIconOutput($iconFilePath, 'svg');
    }
}
