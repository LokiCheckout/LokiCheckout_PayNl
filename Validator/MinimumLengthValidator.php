<?php declare(strict_types=1);

namespace LokiCheckout\PayNl\Validator;

use Loki\Components\Component\ComponentInterface;
use Loki\Components\Validator\ValidatorInterface;

class MinimumLengthValidator implements ValidatorInterface
{
    public function __construct(
        private readonly string $errorMessage,
        private readonly int $minimumLength = 8,
    ) {
    }

    public function validate(mixed $value, ?ComponentInterface $component = null): bool|array
    {
        if (false === is_string($value) && false === is_int($value)) {
            return true;
        }

        $value = trim((string)$value);
        if ($value === '') {
            return true;
        }

        if (mb_strlen($value) < $this->minimumLength) {
            return [__($this->errorMessage)];
        }

        return true;
    }
}
