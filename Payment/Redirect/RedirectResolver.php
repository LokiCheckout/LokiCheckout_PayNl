<?php declare(strict_types=1);

namespace LokiCheckout\PayNl\Payment\Redirect;

use LokiCheckout\Core\Payment\Redirect\RedirectResolverInterface;
use LokiCheckout\Core\Step\FinalStep\RedirectContext;
use Magento\Framework\UrlInterface;
use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Paynl\Payment\Model\Paymentmethod\PaymentMethod;
use Throwable;

class RedirectResolver implements RedirectResolverInterface
{
    public const REDIRECT_ROUTE = 'paynl/checkout/redirect';

    public function __construct(
        private readonly UrlInterface $urlBuilder,
        private readonly QuoteIdToMaskedQuoteIdInterface $quoteIdToMaskedQuoteId,
    ) {
    }

    public function resolve(RedirectContext $redirectContext): false|string
    {
        try {
            $paymentMethod = $redirectContext->getPaymentMethod();
            if (false === $paymentMethod instanceof PaymentMethod) {
                return false;
            }

            $order = $redirectContext->getOrder();
            if (false === $order instanceof OrderInterface) {
                return false;
            }
        } catch (Throwable) {
            return false;
        }

        return $this->urlBuilder->getUrl(self::REDIRECT_ROUTE, [
            '_query' => [
                'nocache' => time(),
                'mqid' => $this->getQuoteIdentifier($order),
            ],
        ]);
    }

    public function getQuoteIdentifier(OrderInterface $order): string
    {
        $quoteId = (int)$order->getQuoteId();
        if ((int)$order->getCustomerId() > 0) {
            return (string)$quoteId;
        }

        try {
            $maskedQuoteId = $this->quoteIdToMaskedQuoteId->execute($quoteId);
        } catch (Throwable) {
            return (string)$quoteId;
        }

        return $maskedQuoteId !== '' ? $maskedQuoteId : (string)$quoteId;
    }
}
