<?php

namespace App\Services\Phone;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class PhoneNumberService
{
    private PhoneNumberUtil $phoneUtil;

    public function __construct()
    {
        $this->phoneUtil = PhoneNumberUtil::getInstance();
    }

    public function normalize(string $number, string $defaultCountry = 'CM'): ?string
    {
        $number = preg_replace('/[\s\-\(\)]+/', '', $number);

        try {
            $parsed = $this->phoneUtil->parse($number, $defaultCountry);

            if (! $this->phoneUtil->isValidNumber($parsed)) {
                return null;
            }

            return $this->phoneUtil->format($parsed, PhoneNumberFormat::E164);
        } catch (NumberParseException) {
            return null;
        }
    }
}
