<?php

namespace WjCrypto\Models\Services;

use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Money;
use Money\Parser\IntlLocalizedDecimalParser;
use WjCrypto\Helpers\ResponseArray;
use WjCrypto\Models\Database\AccountNumberDatabase;

class TransferService
{
    use ResponseArray;

    private $accountService;

    public function transfer()
    {
        $inputedValues = input()->all();
        $result = $this->validateTransferData($inputedValues);
        if (is_array($result)) {
            return $result;
        }
        $destinyAccount = $this->createAccountObject($inputedValues['accountNumber']);
        $userService = new UserService();
        $loggedUserAccount = $userService->getLoggedUserAccount();

    }

    private function validateTransferData(array $inputedValues): ?array
    {
        $requiredFields = [
            'accountNumber',
            'transferValue'
        ];

        $numberOfInputedFields = count($inputedValues);
        $numberOfRequiredFields = count($requiredFields);

        if ($numberOfInputedFields !== $numberOfRequiredFields) {
            $message = 'Error! Invalid number of fields. Please only enter the required fields: accountNumber and transferValue.';
            return $this->generateResponseArray($message, 400);
        }

        foreach ($requiredFields as $requiredField) {
            $isRequiredFieldInRequest = array_key_exists($requiredField, $inputedValues);
            if ($isRequiredFieldInRequest === false) {
                $message = 'Error! The field ' . $requiredField . ' does not exists in the payload.';
                return $this->generateResponseArray($message, 400);
            }
        }

        foreach ($inputedValues as $key => $field) {
            if (empty($field)) {
                $message = 'Error! The field ' . $key . ' is empty.';
                return $this->generateResponseArray($message, 400);
            }
            if (is_string($field) === false) {
                $message = 'Error! The field ' . $key . ' is not a string.';
                return $this->generateResponseArray($message, 400);
            }
        }

        $moneyRegex = '/^[0-9]{1,3}(\.[0-9]{3})*(,[0-9]{2})$/';
        $regexResult = preg_match($moneyRegex, $inputedValues['withdrawValue']);
        if ($regexResult === 0) {
            $message = 'Error! The field transferValue has a invalid money format. Use the following format: xxx.xxx,xx';
            return $this->generateResponseArray($message, 400);
        }

        $accountNumberDatabase = new AccountNumberDatabase();
        $selectAccountNumberResult = $accountNumberDatabase->selectByAccountNumber($inputedValues['accountNumber']);
        if (is_string($selectAccountNumberResult) || $selectAccountNumberResult === false) {
            $message = 'Error! The account number is invalid.';
            return $this->generateResponseArray($message, 400);
        }
        return null;
    }

    private function createAccountObject($accountNumber)
    {
        $accountNumberDatabase = new AccountNumberDatabase();
        $selectAccountNumberResult = $accountNumberDatabase->selectByAccountNumber($accountNumber);

        $naturalPersonAccountId = $selectAccountNumberResult->getNaturalPersonAccountId();
        $legalPersonAccountId = $selectAccountNumberResult->getLegalPersonAccountId();

        if (is_numeric($naturalPersonAccountId) === true && is_null($legalPersonAccountId) === true) {
            $this->accountService = new NaturalPersonAccountService();
            return $this->accountService->generateNaturalPersonAccountObject($accountNumber);
        }

        if (is_numeric($legalPersonAccountId) === true && is_null($naturalPersonAccountId) === true) {
            $this->accountService = new legalPersonAccountService();
            return $this->accountService->generateLegalPersonAccountObject($accountNumber);
        }
        return null;
    }

    /**
     * @param $account
     * @param $withdrawValue1
     */
    private function validateTransfer($account, $withdrawValue1)
    {
        /**
         * @var Money $balance
         */
        $currencies = new ISOCurrencies();
        $numberFormatter = new \NumberFormatter('pt-BR', \NumberFormatter::DECIMAL);
        $moneyParser = new IntlLocalizedDecimalParser($numberFormatter, $currencies);

        $balance = $account->getBalance();
        $withdrawValue = $moneyParser->parse($withdrawValue1, new Currency('BRL'));

        if ($balance->lessThan($withdrawValue)) {
            $message = 'Invalid operation. The account does not have enough amount to make this transfer.';
            return $this->generateResponseArray($message, 400);
        }

        return $balance->subtract($withdrawValue);
    }
}