<?php declare(strict_types=1);

namespace LokiCheckout\PayNl\Payment\Redirect;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Paynl\Payment\Model\Paymentmethod\Paymentmethod;
use LokiCheckout\Core\Payment\Redirect\RedirectResolverInterface;
use LokiCheckout\Core\Step\FinalStep\RedirectContext;

class RedirectResolver implements RedirectResolverInterface
{
    public function resolve(RedirectContext $redirectContext): false|string
    {
        $paymentMethod = $redirectContext->getPaymentMethod();
        // @phpstan-ignore class.notFound
        if (false === $paymentMethod instanceof Paymentmethod) {
            return false;
        }

        /** @var Order $order */
        $order = $redirectContext->getOrder();
        if (false === $order instanceof OrderInterface) {
            return false;
        }

        return $paymentMethod->startTransaction($order);
    }
}
