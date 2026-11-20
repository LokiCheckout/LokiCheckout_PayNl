<?php declare(strict_types=1);

namespace LokiCheckout\PayNl\ViewModel\Options;

use Loki\Components\Component\ComponentViewModelInterface;
use Loki\Field\Util\OptionFactory;
use Loki\Field\Util\OptionModelInterface;
use LokiCheckout\PayNl\Util\InstoreState;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class PinMomentOptions implements OptionModelInterface, ArgumentInterface
{
    public function __construct(
        private readonly OptionFactory $optionFactory,
    ) {
    }

    public function getOptions(ComponentViewModelInterface $viewModel): array
    {
        return [
            $this->optionFactory->create(InstoreState::PINMOMENT_CHECKOUT, (string)__('Pay now')),
            $this->optionFactory->create(InstoreState::PINMOMENT_PICKUP, (string)__('Pay at the pickup location')),
        ];
    }
}
