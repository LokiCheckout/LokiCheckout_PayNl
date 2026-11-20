<?php declare(strict_types=1);

namespace LokiCheckout\PayNl\Payment\Redirect;

use LokiCheckout\Core\Payment\Redirect\RedirectResolverInterface;
use LokiCheckout\Core\Step\FinalStep\RedirectContext;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Paynl\Payment\Model\Paymentmethod\PaymentMethod;
use Throwable;

class RedirectResolver implements RedirectResolverInterface
{
    public const REDIRECT_ROUTE = 'paynl/checkout/redirect';

    public function __construct(
        private readonly UrlInterface $urlBuilder,
    ) {
    }

    public function resolve(RedirectContext $redirectContext): false|string
    {
        try {
            $paymentMethod = $redirectContext->getPaymentMethod();
            if (false === $paymentMethod instanceof PaymentMethod) {
                return false;
            }

            if (false === $redirectContext->getOrder() instanceof OrderInterface) {
                return false;
            }
        } catch (Throwable) {
            return false;
        }

        return $this->urlBuilder->getUrl(self::REDIRECT_ROUTE, ['_query' => ['nocache' => time()]]);
    }
}
