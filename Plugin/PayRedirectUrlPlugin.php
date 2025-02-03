<?php declare(strict_types=1);

namespace Yireo\LokiCheckoutPayNl\Plugin;

use Magento\Payment\Model\MethodInterface as PaymentMethod;
use Magento\Sales\Api\Data\OrderInterface;
use Yireo\LokiCheckout\Step\FinalStep\RedirectUrl;

class PayRedirectUrlPlugin
{
    /**
     * @param RedirectUrl $subject
     * @param string $result
     * @param PaymentMethod $paymentMethod
     * @param OrderInterface $order
     * @return string
     */
    public function afterGet(
        RedirectUrl $subject,
        string $result,
        PaymentMethod $paymentMethod,
        ?OrderInterface $order = null
    ): string {

        if (false === $paymentMethod instanceof \Paynl\Payment\Model\Paymentmethod\Paymentmethod) {
            return $result;
        }

        if (false === $order instanceof OrderInterface) {
            return $result;
        }

        return $paymentMethod->startTransaction($order);
    }
}
