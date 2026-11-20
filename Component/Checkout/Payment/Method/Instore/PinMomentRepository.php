<?php declare(strict_types=1);

namespace LokiCheckout\PayNl\Component\Checkout\Payment\Method\Instore;

use LokiCheckout\Core\Component\Base\Generic\CheckoutContext;
use LokiCheckout\Core\Component\Base\Payment\AdditionalInformation\AdditionalInformationRepository;
use LokiCheckout\PayNl\Provider\TerminalProvider;
use LokiCheckout\PayNl\Util\InstoreState;

/**
 * @method CheckoutContext getContext()
 */
class PinMomentRepository extends AdditionalInformationRepository
{
    public function __construct(
        private readonly TerminalProvider $terminalProvider,
    ) {
    }

    public function getPropertyName(): string
    {
        return InstoreState::KEY_PINMOMENT;
    }

    public function getValue(): mixed
    {
        $value = $this->getPropertyValue(InstoreState::PINMOMENT_CHECKOUT);

        return $this->normalize($value);
    }

    public function getDefaultValue(): mixed
    {
        return InstoreState::PINMOMENT_CHECKOUT;
    }

    public function saveValue(mixed $value): void
    {
        $value = $this->normalize($value);
        $this->saveProperty($value);

        $pinMomentTerminal = $this->terminalProvider->getPinMomentTerminal();
        if ($pinMomentTerminal === TerminalProvider::NO_TERMINAL) {
            return;
        }

        if ($value !== InstoreState::PINMOMENT_PICKUP) {
            if ($this->getPropertyValue('', InstoreState::KEY_PAYMENT_OPTION) === $pinMomentTerminal) {
                $this->saveProperty('', InstoreState::KEY_PAYMENT_OPTION);
            }

            return;
        }

        $this->saveProperty($pinMomentTerminal, InstoreState::KEY_PAYMENT_OPTION);
    }

    private function normalize(mixed $value): string
    {
        if (is_scalar($value) && (string)$value === InstoreState::PINMOMENT_PICKUP) {
            return InstoreState::PINMOMENT_PICKUP;
        }

        return InstoreState::PINMOMENT_CHECKOUT;
    }
}
