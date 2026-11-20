<?php declare(strict_types=1);

namespace LokiCheckout\PayNl\Component\Checkout\Payment\Method\Instore;

use Loki\Field\Util\OptionModelInterface;
use LokiCheckout\Core\Component\Base\Generic\CheckoutContext;
use LokiCheckout\Core\Component\Base\Payment\AdditionalInformation\AdditionalInformationViewModel;
use LokiCheckout\PayNl\Util\InstoreState;
use LokiCheckout\PayNl\ViewModel\Options\PinMomentOptions;

/**
 * @method CheckoutContext getContext()
 */
class PinMomentViewModel extends AdditionalInformationViewModel
{
    public function __construct(
        private readonly InstoreState $instoreState,
        private readonly PinMomentOptions $pinMomentOptions,
    ) {
    }

    public function isAllowRendering(): bool
    {
        $quote = $this->getContext()->getCheckoutState()->getQuote();
        if (false === $this->instoreState->isPinMomentChoiceAvailable($quote)) {
            return false;
        }

        return parent::isAllowRendering();
    }

    public function isRequired(): bool
    {
        return true;
    }

    public function getOptionModel(): ?OptionModelInterface
    {
        return $this->pinMomentOptions;
    }
}
