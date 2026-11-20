<?php

declare(strict_types=1);

namespace LokiCheckout\PayNl\Test\Integration\Traits;

use Loki\Components\Component\ComponentInterface;
use Loki\Components\Component\ComponentRegistry;
use LokiCheckout\Core\ViewModel\CheckoutState;
use LokiCheckout\PayNl\Provider\TerminalProvider;
use LokiCheckout\PayNl\Util\InstoreState;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteRepository;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;

trait PayNlTestTrait
{
    private array $originalStoreConfig = [];

    private array $originalDefaultConfig = [];

    private function setStoreConfigValue(string $path, mixed $value): void
    {
        if (false === array_key_exists($path, $this->originalStoreConfig)) {
            $this->originalStoreConfig[$path] = $this->getMutableScopeConfig()->getValue($path, 'store', 'default');
        }

        $this->getMutableScopeConfig()->setValue($path, $value, 'store', 'default');
    }

    private function setDefaultConfigValue(string $path, mixed $value): void
    {
        if (false === array_key_exists($path, $this->originalDefaultConfig)) {
            $this->originalDefaultConfig[$path] = $this->getMutableScopeConfig()->getValue($path);
        }

        $this->getMutableScopeConfig()->setValue($path, $value);
    }

    private function restoreConfig(): void
    {
        foreach ($this->originalStoreConfig as $path => $value) {
            $this->getMutableScopeConfig()->setValue($path, $value, 'store', 'default');
        }

        foreach ($this->originalDefaultConfig as $path => $value) {
            $this->getMutableScopeConfig()->setValue($path, $value);
        }

        $this->originalStoreConfig = [];
        $this->originalDefaultConfig = [];
    }

    private function resetPayNlServices(): void
    {
        $objectManager = $this->getTestObjectManager();
        $objectManager->removeSharedInstance(TerminalProvider::class);
        $objectManager->removeSharedInstance(InstoreState::class);
    }

    private function initCart(string $paymentMethod, string $company = ''): Quote
    {
        $objectManager = $this->getTestObjectManager();
        $objectManager->get(QuoteRepository::class)->_resetState();
        $fixtures = $objectManager->get(DataFixtureStorageManager::class)->getStorage();
        $cartId = (int)$fixtures->get('cart')->getId();

        $cartRepository = $objectManager->get(CartRepositoryInterface::class);
        /** @var Quote $quote */
        $quote = $cartRepository->get($cartId);
        $quote->getPayment()->setMethod($paymentMethod);
        $quote->getBillingAddress()->setCompany($company);
        $quote->collectTotals();
        $cartRepository->save($quote);

        /** @var Quote $quote */
        $quote = $objectManager->create(CartRepositoryInterface::class)->get($cartId);
        $checkoutState = $objectManager->get(CheckoutState::class);
        $checkoutState->setQuote($quote);
        $checkoutState->setQuoteId($cartId);
        $objectManager->get(CheckoutSession::class)->replaceQuote($quote);

        return $quote;
    }

    private function reloadQuote(Quote $quote): Quote
    {
        return $this->getTestObjectManager()->create(Quote::class)->load($quote->getId());
    }

    private function getComponentByName(string $blockName): ComponentInterface
    {
        $layout = $this->getTestObjectManager()->get(LayoutInterface::class);
        $block = $layout->getBlock($blockName);
        if (false === $block) {
            $layout->createBlock(Template::class, $blockName);
        }

        return $this->getTestObjectManager()->create(ComponentRegistry::class)->getComponentByName($blockName);
    }

    private function getTestObjectManager(): ObjectManager
    {
        /** @var ObjectManager $objectManager */
        $objectManager = Bootstrap::getObjectManager();

        return $objectManager;
    }

    private function getMutableScopeConfig(): MutableScopeConfigInterface
    {
        return Bootstrap::getObjectManager()->get(MutableScopeConfigInterface::class);
    }
}
