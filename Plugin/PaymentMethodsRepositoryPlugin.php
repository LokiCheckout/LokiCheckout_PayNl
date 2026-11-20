<?php declare(strict_types=1);

namespace LokiCheckout\PayNl\Plugin;

use LokiCheckout\Core\Component\Checkout\Billing\PaymentMethods\PaymentMethodsRepository;
use LokiCheckout\Core\ViewModel\CheckoutState;
use LokiCheckout\PayNl\Provider\TerminalProvider;
use LokiCheckout\PayNl\Util\InstoreState;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\ScopeInterface;
use Paynl\Payment\Model\Paymentmethod\PaymentMethod;
use Throwable;

class PaymentMethodsRepositoryPlugin
{
    public const DEFAULT_PAYMENT_OPTION_PATH = 'payment/paynl/default_payment_option';

    /**
     * @var array<int, list<string>>
     */
    private array $filteredCodes = [];

    public function __construct(
        private readonly CheckoutState $checkoutState,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly TerminalProvider $terminalProvider,
        private readonly InstoreState $instoreState,
    ) {
    }

    public function afterGetDefaultPayment(PaymentMethodsRepository $subject, string $result): string
    {
        $defaultMethod = trim((string)$this->scopeConfig->getValue(
            self::DEFAULT_PAYMENT_OPTION_PATH,
            ScopeInterface::SCOPE_STORE
        ), " \f\n\r\t\v\x00");

        if ($defaultMethod === '' || $defaultMethod === '0' || $defaultMethod === $result) {
            return $result;
        }

        $codes = $this->filteredCodes[spl_object_id($subject)] ?? null;
        if ($codes === null) {
            $codes = array_map(
                static fn (MethodInterface $method): string => (string)$method->getCode(),
                $subject->getPaymentMethods()
            );
            $this->filteredCodes[spl_object_id($subject)] = $codes;
        }

        return in_array($defaultMethod, $codes, true) ? $defaultMethod : $result;
    }

    /**
     * @param PaymentMethodsRepository $subject
     * @param MethodInterface[] $result
     * @return MethodInterface[]
     */
    public function afterGetPaymentMethods(PaymentMethodsRepository $subject, array $result): array
    {
        $quote = $this->checkoutState->getQuote();

        $filtered = array_values(array_filter(
            $result,
            fn (MethodInterface $method): bool => $this->isAllowed($method, $quote)
        ));

        $this->filteredCodes[spl_object_id($subject)] = array_map(
            static fn (MethodInterface $method): string => (string)$method->getCode(),
            $filtered
        );

        return $filtered;
    }

    public function isAllowed(MethodInterface $method, Quote $quote): bool
    {
        $code = (string)$method->getCode();
        if (false === str_starts_with($code, 'paynl_')) {
            return true;
        }

        if (false === $this->isAllowedForCustomerGroup($code, $quote)) {
            return false;
        }

        if (false === $this->isAllowedForShippingMethod($code, $quote)) {
            return false;
        }

        if (false === $this->isAllowedForCompany($code, $quote)) {
            return false;
        }

        if (false === $this->isAllowedForClient($method)) {
            return false;
        }

        if ($code === TerminalProvider::METHOD_CODE) {
            return $this->isInstoreAllowed($quote);
        }

        return true;
    }

    private function isAllowedForCustomerGroup(string $code, Quote $quote): bool
    {
        $groups = $this->getListConfig($code, 'showforgroup');
        if ($groups === []) {
            return true;
        }

        return in_array((string)(int)$quote->getCustomerGroupId(), $groups, true);
    }

    private function isAllowedForShippingMethod(string $code, Quote $quote): bool
    {
        $disallowedShippingMethods = $this->getListConfig($code, 'disallowedshipping');
        if ($disallowedShippingMethods === []) {
            return true;
        }

        $shippingMethod = (string)$quote->getShippingAddress()->getShippingMethod();
        if ($shippingMethod === '') {
            return true;
        }

        return false === in_array($shippingMethod, $disallowedShippingMethods, true);
    }

    private function isAllowedForCompany(string $code, Quote $quote): bool
    {
        $showForCompany = (int)$this->getConfig($code, 'showforcompany');
        $hasCompany = trim((string)$quote->getBillingAddress()->getCompany(), " \f\n\r\t\v\x00") !== '';

        return match ($showForCompany) {
            1 => false === $hasCompany,
            2 => $hasCompany,
            default => true,
        };
    }

    private function isAllowedForClient(MethodInterface $method): bool
    {
        if (false === $method instanceof PaymentMethod) {
            return true;
        }

        try {
            return (bool)$method->isCurrentIpValid() && (bool)$method->isCurrentAgentValid();
        } catch (Throwable) {
            return true;
        }
    }

    private function isInstoreAllowed(Quote $quote): bool
    {
        if ($this->instoreState->getPinMomentConfig() === InstoreState::PINMOMENT_PICKUP
            && false === $this->instoreState->isPickupShippingMethod($quote)) {
            return false;
        }

        if ($this->terminalProvider->getTerminals() !== []) {
            return true;
        }

        return $this->instoreState->hasDefaultTerminal() || $this->instoreState->hasPinMomentTerminal();
    }

    /**
     * @return list<string>
     */
    private function getListConfig(string $code, string $key): array
    {
        $values = array_map('trim', explode(',', $this->getConfig($code, $key)));

        return array_values(array_filter($values, static fn (string $value): bool => $value !== ''));
    }

    private function getConfig(string $code, string $key): string
    {
        return trim((string)$this->scopeConfig->getValue(
            'payment/' . $code . '/' . $key,
            ScopeInterface::SCOPE_STORE
        ), " \f\n\r\t\v\x00");
    }
}
