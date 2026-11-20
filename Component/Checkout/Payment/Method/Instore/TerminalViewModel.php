<?php declare(strict_types=1);

namespace LokiCheckout\PayNl\Component\Checkout\Payment\Method\Instore;

use LokiCheckout\Core\Component\Base\Generic\CheckoutContext;
use LokiCheckout\Core\Component\Base\Payment\AdditionalInformation\AdditionalInformationViewModel;
use LokiCheckout\PayNl\Provider\TerminalProvider;
use LokiCheckout\PayNl\Util\InstoreState;

/**
 * @method CheckoutContext getContext()
 */
class TerminalViewModel extends AdditionalInformationViewModel
{
    public function __construct(
        private readonly TerminalProvider $terminalProvider,
        private readonly InstoreState $instoreState,
    ) {
    }

    /**
     * @return list<array{id: string, label: string}>
     */
    public function getTerminals(): array
    {
        return $this->terminalProvider->getTerminals();
    }

    public function isAllowRendering(): bool
    {
        if (false === $this->isTerminalSelectionVisible()) {
            return false;
        }

        return parent::isAllowRendering();
    }

    public function isTerminalSelectionVisible(): bool
    {
        if ($this->instoreState->isTerminalSelectionHidden() && $this->instoreState->hasDefaultTerminal()) {
            return false;
        }

        $quote = $this->getContext()->getCheckoutState()->getQuote();
        if ($this->instoreState->isPayAtPickup($quote) && $this->instoreState->hasPinMomentTerminal()) {
            return false;
        }

        return true;
    }

    public function isRequired(): bool
    {
        return $this->isTerminalSelectionVisible();
    }

    public function getFieldName(): string
    {
        return 'paynl_instore_terminal';
    }

    public function getFieldLabel(): string
    {
        return (string)__('Select terminal');
    }
}
