<?php declare(strict_types=1);

namespace LokiCheckout\PayNl\Component\Checkout\Payment\Method\Instore;

use LokiCheckout\Core\Component\Base\Generic\CheckoutContext;
use LokiCheckout\Core\Component\Base\Payment\AdditionalInformation\AdditionalInformationRepository;
use LokiCheckout\PayNl\Provider\TerminalProvider;
use LokiCheckout\PayNl\Util\InstoreState;

/**
 * @method CheckoutContext getContext()
 */
class TerminalRepository extends AdditionalInformationRepository
{
    public function __construct(
        private readonly TerminalProvider $terminalProvider,
    ) {
    }

    public function getPropertyName(): string
    {
        return InstoreState::KEY_PAYMENT_OPTION;
    }

    public function getValue(): mixed
    {
        $value = $this->getPropertyValue('');
        $terminalId = is_scalar($value) ? trim((string)$value, " \f\n\r\t\v\x00") : '';
        if ($terminalId !== '' && $this->isValidTerminal($terminalId)) {
            return $terminalId;
        }

        $defaultValue = (string)$this->getDefaultValue();
        if ($defaultValue === '') {
            return null;
        }

        $this->saveProperty($defaultValue);

        return $defaultValue;
    }

    public function getDefaultValue(): mixed
    {
        $defaultTerminal = $this->terminalProvider->getDefaultTerminal();
        if ($defaultTerminal !== TerminalProvider::NO_TERMINAL && $this->isValidTerminal($defaultTerminal)) {
            return $defaultTerminal;
        }

        $terminals = $this->terminalProvider->getTerminals();
        if (count($terminals) === 1) {
            return $terminals[0]['id'];
        }

        return '';
    }

    public function saveValue(mixed $value): void
    {
        $terminalId = is_scalar($value) ? trim((string)$value, " \f\n\r\t\v\x00") : '';
        if (false === $this->isValidTerminal($terminalId)) {
            if ($terminalId !== '') {
                $this->getLocalMessageRegistry()->addError((string)__('Please select a valid terminal'));
            }

            return;
        }

        $this->saveProperty($terminalId);
    }

    private function isValidTerminal(string $terminalId): bool
    {
        if ($terminalId === '') {
            return false;
        }

        if ($this->terminalProvider->getTerminalById($terminalId) !== null) {
            return true;
        }

        return in_array(
            $terminalId,
            [$this->terminalProvider->getDefaultTerminal(), $this->terminalProvider->getPinMomentTerminal()],
            true
        ) && $terminalId !== TerminalProvider::NO_TERMINAL;
    }
}
