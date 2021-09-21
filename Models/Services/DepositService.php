<?php

namespace WjCrypto\Models\Services;

use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Parser\IntlLocalizedDecimalParser;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use WjCrypto\Helpers\ResponseArray;
use WjCrypto\Models\Database\AccountNumberDatabase;
use WjCrypto\Models\Entities\LegalPersonAccount;
use WjCrypto\Models\Entities\NaturalPersonAccount;

class DepositService
{
    use ResponseArray;

    public function makeDepositIntoAccount(): array
    {
        $inputedValues = input()->all();
        $validationResult = $this->validateDepositData($inputedValues);
        if (is_array($validationResult)) {
            return $validationResult;
        }
        $accountNumberDatabase = new AccountNumberDatabase();
        $selectAccountNumberResult = $accountNumberDatabase->selectByAccountNumber($inputedValues['accountNumber']);

        $naturalPersonAccountId = $selectAccountNumberResult->getNaturalPersonAccountId();
        $legalPersonAccountId = $selectAccountNumberResult->getLegalPersonAccountId();

        if (is_numeric($naturalPersonAccountId) === true && is_null($legalPersonAccountId) === true) {
            $naturalPersonAccountService = new NaturalPersonAccountService();
            $account = $naturalPersonAccountService->generateNaturalPersonAccountObject(
                $inputedValues['accountNumber']
            );
            return $this->makeTheDeposit(
                $account,
                $inputedValues['depositValue'],
                $naturalPersonAccountService,
                $naturalPersonAccountId
            );
        }
        if (is_numeric($legalPersonAccountId) === true && is_null($naturalPersonAccountId) === true) {
            $legalPersonAccountService = new legalPersonAccountService();
            $account = $legalPersonAccountService->generateLegalPersonAccountObject($inputedValues['accountNumber']);
            return $this->makeTheDeposit(
                $account,
                $inputedValues['depositValue'],
                $legalPersonAccountService,
                $legalPersonAccountId
            );
        }

        $message = 'An error occurred.';
        return $this->generateResponseArray($message, 400);
    }

    private function validateDepositData(array $inputedValues): ?array
    {
        $requiredFields = [
            'accountNumber',
            'depositValue'
        ];

        $numberOfInputedFields = count($inputedValues);
        $numberOfRequiredFields = count($requiredFields);

        if ($numberOfInputedFields !== $numberOfRequiredFields) {
            $message = 'Error! Invalid number of fields. Please only enter the required fields: accountNumber and depositValue.';
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
        $regexResult = preg_match($moneyRegex, $inputedValues['depositValue']);
        if ($regexResult === 0) {
            $message = 'Error! The field depositValue has a invalid money format. Use the following format: xxx.xxx,xx';
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

    /**
     * @param NaturalPersonAccount|LegalPersonAccount $account
     * @param $depositValueString
     * @param $accountService
     * @param $accountId
     * @return array
     */
    private function makeTheDeposit(
        NaturalPersonAccount|LegalPersonAccount $account,
        $depositValueString,
        $accountService,
        $accountId
    ): array {
        $currencies = new ISOCurrencies();
        $numberFormatter = new \NumberFormatter('pt-BR', \NumberFormatter::DECIMAL);
        $moneyParser = new IntlLocalizedDecimalParser($numberFormatter, $currencies);

        $balance = $account->getBalance();
        $depositValue = $moneyParser->parse($depositValueString, new Currency('BRL'));

        $newBalance = $balance->add($depositValue);

        $result = $accountService->updateBalance($newBalance->getAmount(), $accountId);
        if ($result === false) {
            $message = 'Error!';
            return $this->generateResponseArray($message, 400);
        }
        $message = 'Success!';
        $this->registerLog(
            'Deposit made into the account: ' . $account->getAccountNumber()->getAccountNumber(
            ) . ' with value: ' . $depositValue->getAmount()
        );
        return $this->generateResponseArray($message, 200);
    }

    private function registerLog(string $message)
    {
        $logger = new Logger('login');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../../Logs/transaction.log', Logger::INFO));
        $logger->info($message);
    }
}

