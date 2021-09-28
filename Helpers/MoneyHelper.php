<?php

namespace WjCrypto\Helpers;

use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Money;
use Money\Parser\IntlLocalizedDecimalParser;

trait MoneyHelper
{
    /**
     * @param string $value
     * @return Money
     */
    private function convertStringToMoney(string $value): Money
    {
        $currencies = new ISOCurrencies();
        $numberFormatter = new \NumberFormatter('pt-BR', \NumberFormatter::DECIMAL);
        $moneyParser = new IntlLocalizedDecimalParser($numberFormatter, $currencies);
        return $moneyParser->parse($value, new Currency('BRL'));
    }
}
