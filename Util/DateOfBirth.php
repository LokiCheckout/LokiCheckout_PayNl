<?php declare(strict_types=1);

namespace LokiCheckout\PayNl\Util;

use DateTimeImmutable;

class DateOfBirth
{
    public const PAYNL_FORMAT = 'd-m-Y';
    public const INPUT_FORMAT = 'Y-m-d';

    private const SUPPORTED_FORMATS = ['!Y-m-d', '!d-m-Y', '!Y-m-d H:i:s', '!d/m/Y'];

    public function parse(string $value): ?DateTimeImmutable
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        foreach (self::SUPPORTED_FORMATS as $format) {
            $date = DateTimeImmutable::createFromFormat($format, $value);
            if ($date === false) {
                continue;
            }

            $errors = DateTimeImmutable::getLastErrors();
            if (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
                continue;
            }

            return $date;
        }

        return null;
    }

    public function toPayNlFormat(string $value): string
    {
        $date = $this->parse($value);
        if ($date === null) {
            return '';
        }

        return $date->format(self::PAYNL_FORMAT);
    }

    public function toInputFormat(string $value): string
    {
        $date = $this->parse($value);
        if ($date === null) {
            return '';
        }

        return $date->format(self::INPUT_FORMAT);
    }
}
