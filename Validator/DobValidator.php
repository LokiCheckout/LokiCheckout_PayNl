<?php declare(strict_types=1);

namespace LokiCheckout\PayNl\Validator;

use DateTimeImmutable;
use Loki\Components\Component\ComponentInterface;
use Loki\Components\Validator\ValidatorInterface;
use LokiCheckout\PayNl\Util\DateOfBirth;

class DobValidator implements ValidatorInterface
{
    public function __construct(
        private readonly DateOfBirth $dateOfBirth,
    ) {
    }

    public function validate(mixed $value, ?ComponentInterface $component = null): bool|array
    {
        if (false === is_string($value) || trim($value, " \f\n\r\t\v\x00") === '') {
            return true;
        }

        $date = $this->dateOfBirth->parse($value);
        if ($date === null) {
            return [__('Please enter a valid date')];
        }

        if ($date >= new DateTimeImmutable('today')) {
            return [__('Please enter a valid date of birth')];
        }

        return true;
    }
}
