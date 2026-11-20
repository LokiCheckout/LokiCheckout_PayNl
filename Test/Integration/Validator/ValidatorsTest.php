<?php

declare(strict_types=1);

namespace LokiCheckout\PayNl\Test\Integration\Validator;

use Loki\Components\Validator\ValidatorInterface;
use Loki\Components\Validator\ValidatorRegistry;
use LokiCheckout\PayNl\Validator\DobValidator;
use LokiCheckout\PayNl\Validator\MinimumLengthValidator;
use Magento\Framework\App\ObjectManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ValidatorsTest extends TestCase
{
    public function testValidatorsAreRegistered(): void
    {
        $validators = $this->getRegistry()->getApplicableValidators(['paynl_dob', 'paynl_coc', 'paynl_vat']);

        $this->assertSame(['paynl_dob', 'paynl_coc', 'paynl_vat'], array_keys($validators));
        $this->assertInstanceOf(DobValidator::class, $validators['paynl_dob']);
        $this->assertInstanceOf(MinimumLengthValidator::class, $validators['paynl_coc']);
        $this->assertInstanceOf(MinimumLengthValidator::class, $validators['paynl_vat']);
    }

    public static function getDobCases(): array
    {
        return [
            'empty' => ['', true],
            'whitespace' => ['  ', true],
            'not a string' => [null, true],
            'input format' => ['1980-01-31', true],
            'PAY. format' => ['31-01-1980', true],
            'invalid' => ['foobar', 'Please enter a valid date'],
            'impossible date' => ['1980-02-31', 'Please enter a valid date'],
            'today' => [date('Y-m-d'), 'Please enter a valid date of birth'],
            'future' => [date('Y-m-d', strtotime('+1 year')), 'Please enter a valid date of birth'],
        ];
    }

    #[DataProvider('getDobCases')]
    public function testDobValidator(mixed $value, bool|string $expected): void
    {
        $this->assertValidation('paynl_dob', $value, $expected);
    }

    public static function getMinimumLengthCases(): array
    {
        return [
            'coc empty' => ['paynl_coc', '', true],
            'coc too short' => ['paynl_coc', '1234567', 'Please enter a valid CoC number'],
            'coc too short after trim' => ['paynl_coc', ' 1234567 ', 'Please enter a valid CoC number'],
            'coc valid' => ['paynl_coc', '12345678', true],
            'coc integer' => ['paynl_coc', 12345678, true],
            'coc short integer' => ['paynl_coc', 1234, 'Please enter a valid CoC number'],
            'vat empty' => ['paynl_vat', '', true],
            'vat too short' => ['paynl_vat', 'NL12345', 'Please enter a valid VAT number'],
            'vat valid' => ['paynl_vat', 'NL123456789B01', true],
            'vat array' => ['paynl_vat', ['x'], true],
        ];
    }

    #[DataProvider('getMinimumLengthCases')]
    public function testMinimumLengthValidators(string $validatorName, mixed $value, bool|string $expected): void
    {
        $this->assertValidation($validatorName, $value, $expected);
    }

    private function assertValidation(string $validatorName, mixed $value, bool|string $expected): void
    {
        $validators = $this->getRegistry()->getApplicableValidators([$validatorName]);
        $this->assertArrayHasKey($validatorName, $validators);
        $validator = $validators[$validatorName];
        $this->assertInstanceOf(ValidatorInterface::class, $validator);

        $result = $validator->validate($value);
        if (is_bool($expected)) {
            $this->assertSame($expected, $result);
            return;
        }

        $this->assertIsArray($result);
        $this->assertSame([$expected], array_map('strval', $result));
    }

    private function getRegistry(): ValidatorRegistry
    {
        return ObjectManager::getInstance()->get(ValidatorRegistry::class);
    }
}
