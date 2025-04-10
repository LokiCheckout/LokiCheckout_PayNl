<?php declare(strict_types=1);

namespace Yireo\LokiCheckoutPayNl\Payment\Redirect;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Paynl\Payment\Model\Paymentmethod\Paymentmethod;
use Yireo\LokiCheckout\Payment\Redirect\RedirectResolverInterface;
use Yireo\LokiCheckout\Step\FinalStep\RedirectContext;

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
