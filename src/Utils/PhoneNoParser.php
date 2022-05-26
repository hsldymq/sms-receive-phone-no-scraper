<?php

declare(strict_types=1);

namespace Hsldymq\SMS\Utils;

class PhoneNoParser
{
    public static function parsePhone(string $phoneNo, string $countryCode): array
    {
        $country = country($countryCode);
        $callingCodes = $country->getCallingCodes();
        foreach ($callingCodes as $each) {
            if (str_starts_with($phoneNo, $each)) {
                return [$each, substr($phoneNo, strlen($each))];
            }
        }

        return ['', $phoneNo];
    }
}
