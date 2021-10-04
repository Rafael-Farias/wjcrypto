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

    /**
     * @param Money $value
     * @return string
     */
    public function getParsedBalance(Money $value): string
    {
        $balance = $value->getAmount();
        $decimal = substr($balance, -2);
        $integer = substr($balance, 0, -2);
        $stringBalance = $integer . '.' . $decimal;
        return number_format((float)$stringBalance, 2, ',', '.');
    }
}
