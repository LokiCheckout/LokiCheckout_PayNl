<?php declare(strict_types=1);

namespace LokiCheckout\PayNl\Util;

use LokiCheckout\PayNl\Provider\TerminalProvider;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\ScopeInterface;

class InstoreState
{
    public const PICKUP_SHIPPING_METHOD = 'instore_pickup';

    public const PINMOMENT_CHECKOUT = '0';
    public const PINMOMENT_PICKUP = '1';
    public const PINMOMENT_CHOICE = '2';

    public const KEY_PINMOMENT = 'pinmoment';
    public const KEY_PAYMENT_OPTION = 'payment_option';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly TerminalProvider $terminalProvider,
    ) {
    }

    public function getPinMomentConfig(): string
    {
        $value = trim((string)$this->scopeConfig->getValue(
            'payment/' . TerminalProvider::METHOD_CODE . '/pinmoment',
            ScopeInterface::SCOPE_STORE
        ), " \f\n\r\t\v\x00");

        return in_array($value, [self::PINMOMENT_PICKUP, self::PINMOMENT_CHOICE], true)
            ? $value
            : self::PINMOMENT_CHECKOUT;
    }

    public function isTerminalSelectionHidden(): bool
    {
        return $this->scopeConfig->isSetFlag(
            'payment/' . TerminalProvider::METHOD_CODE . '/hide_terminal_selection',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function isPickupShippingMethod(CartInterface $quote): bool
    {
        if (false === $quote instanceof Quote) {
            return false;
        }

        return (string)$quote->getShippingAddress()->getShippingMethod() === self::PICKUP_SHIPPING_METHOD;
    }

    public function isPinMomentChoiceAvailable(CartInterface $quote): bool
    {
        return $this->getPinMomentConfig() === self::PINMOMENT_CHOICE && $this->isPickupShippingMethod($quote);
    }

    public function isPayAtPickup(CartInterface $quote): bool
    {
        $pinMomentConfig = $this->getPinMomentConfig();
        if ($pinMomentConfig === self::PINMOMENT_PICKUP) {
            return $this->isPickupShippingMethod($quote);
        }

        if (false === $this->isPinMomentChoiceAvailable($quote)) {
            return false;
        }

        $payment = $quote instanceof Quote ? $quote->getPayment() : null;
        if ($payment === null) {
            return false;
        }

        return (string)$payment->getAdditionalInformation(self::KEY_PINMOMENT) === self::PINMOMENT_PICKUP;
    }

    public function hasPinMomentTerminal(): bool
    {
        return $this->terminalProvider->getPinMomentTerminal() !== TerminalProvider::NO_TERMINAL;
    }

    public function hasDefaultTerminal(): bool
    {
        return $this->terminalProvider->getDefaultTerminal() !== TerminalProvider::NO_TERMINAL;
    }
}
