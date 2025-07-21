<?php declare(strict_types=1);

namespace LokiCheckout\PayNl\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Paynl\Payment\ViewModel\FastCheckout;
use LokiCheckout\Core\ViewModel\CheckoutState;

class IdealFastCheckout implements ArgumentInterface
{
    public function __construct(
        private FastCheckout $fastCheckout,
        private CheckoutState $checkoutState,
    ) {
    }

    public function enabled(): bool
    {
        return $this->fastCheckout->getVisibility();
    }

    public function modalEnabled(): bool
    {
        return $this->fastCheckout->modalEnabled();
    }

    public function getShippingAddress(): AddressInterface
    {
        return $this->checkoutState->getQuote()->getShippingAddress();
    }
}
