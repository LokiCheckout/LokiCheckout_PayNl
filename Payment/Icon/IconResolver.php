<?php declare(strict_types=1);

namespace LokiCheckout\PayNl\Payment\Icon;

use LokiCheckout\Core\Payment\Icon\IconResolverContext;
use LokiCheckout\Core\Payment\Icon\IconResolverInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Store\Model\ScopeInterface;
use Paynl\Payment\Model\Config;

class IconResolver implements IconResolverInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly ModuleManager $moduleManager,
        private readonly ScopeConfigInterface $scopeConfig,
    ) {
    }

    public function resolve(IconResolverContext $iconResolverContext): false|string
    {
        $paymentMethodCode = $iconResolverContext->getPaymentMethodCode();
        if (false === $this->moduleManager->isEnabled('Paynl_Payment')) {
            return false;
        }

        if (false === str_starts_with($paymentMethodCode, 'paynl_')) {
            return false;
        }

        $htmlAttributes = [
            'alt' => $this->getTitle($paymentMethodCode),
        ];

        return $iconResolverContext->getIconOutput(
            $this->config->getIconUrl($paymentMethodCode),
            htmlAttributes: $htmlAttributes,
        );
    }

    public function getTitle(string $paymentMethodCode): string
    {
        $title = trim((string)$this->scopeConfig->getValue(
            'payment/' . $paymentMethodCode . '/title',
            ScopeInterface::SCOPE_STORE
        ));

        return $title !== '' ? $title : $paymentMethodCode;
    }
}
