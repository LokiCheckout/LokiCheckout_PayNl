<?php declare(strict_types=1);

namespace LokiCheckout\PayNl\Provider;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Store\Model\ScopeInterface;
use Throwable;

class TerminalProvider
{
    public const METHOD_CODE = 'paynl_payment_instore';
    public const NO_TERMINAL = '0';

    /**
     * @var list<array{id: string, label: string}>|null
     */
    private ?array $terminals = null;

    public function __construct(
        private readonly PaymentHelper $paymentHelper,
        private readonly ScopeConfigInterface $scopeConfig,
    ) {
    }

    /**
     * @return list<array{id: string, label: string}>
     */
    public function getTerminals(): array
    {
        if ($this->terminals !== null) {
            return $this->terminals;
        }

        $this->terminals = $this->normalize($this->fetchPaymentOptions());

        return $this->terminals;
    }

    /**
     * @return array{id: string, label: string}|null
     */
    public function getTerminalById(string $terminalId): ?array
    {
        if ($terminalId === '') {
            return null;
        }

        foreach ($this->getTerminals() as $terminal) {
            if ($terminal['id'] === $terminalId) {
                return $terminal;
            }
        }

        return null;
    }

    public function getDefaultTerminal(): string
    {
        return $this->getConfigValue('default_terminal');
    }

    public function getPinMomentTerminal(): string
    {
        return $this->getConfigValue('pinmoment_terminal');
    }

    private function getConfigValue(string $key): string
    {
        $value = trim((string)$this->scopeConfig->getValue(
            'payment/' . self::METHOD_CODE . '/' . $key,
            ScopeInterface::SCOPE_STORE
        ), " \f\n\r\t\v\x00");

        return $value === '' ? self::NO_TERMINAL : $value;
    }

    private function fetchPaymentOptions(): mixed
    {
        try {
            $methodInstance = $this->paymentHelper->getMethodInstance(self::METHOD_CODE);
            if (false === method_exists($methodInstance, 'getPaymentOptions')) {
                return [];
            }

            return $methodInstance->getPaymentOptions();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @return list<array{id: string, label: string}>
     */
    private function normalize(mixed $paymentOptions): array
    {
        if (false === is_iterable($paymentOptions)) {
            return [];
        }

        $terminals = [];
        foreach ($paymentOptions as $paymentOption) {
            if (is_object($paymentOption)) {
                $paymentOption = get_object_vars($paymentOption);
            }

            if (false === is_array($paymentOption)) {
                continue;
            }

            $id = $this->toString($paymentOption['id'] ?? '');
            if ($id === '') {
                continue;
            }

            $label = $this->toString($paymentOption['visibleName'] ?? '');
            if ($label === '') {
                $label = $this->toString($paymentOption['name'] ?? '');
            }

            $terminals[] = [
                'id' => $id,
                'label' => $label !== '' ? $label : $id,
            ];
        }

        return $terminals;
    }

    private function toString(mixed $value): string
    {
        if (is_string($value) || is_int($value) || is_float($value)) {
            return trim((string)$value, " \f\n\r\t\v\x00");
        }

        return '';
    }
}
