<?php

namespace WjCrypto\Helpers;

use CodeInc\StripAccents\StripAccents;

trait SanitizeString
{
    public function sanitizeString(string $stringToSanitize): string
    {
        $stringWithoutAccents = StripAccents::strip($stringToSanitize);
        $stringWithoutAccentsAndQuotationMark = preg_replace('/\'/', '', $stringWithoutAccents);
        return strtolower($stringWithoutAccentsAndQuotationMark);
    }
}