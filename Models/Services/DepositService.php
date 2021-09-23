<?php

namespace WjCrypto\Models\Services;

use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Parser\IntlLocalizedDecimalParser;
use Monolog\Logger;
use WjCrypto\Helpers\JsonResponse;
use WjCrypto\Helpers\LogHelper;
use WjCrypto\Helpers\ResponseArray;
use WjCrypto\Models\Database\AccountNumberDatabase;
use WjCrypto\Models\Entities\LegalPersonAccount;
use WjCrypto\Models\Entities\NaturalPersonAccount;

class DepositService
{
    use ResponseArray;
    use LogHelper;
    use JsonResponse;

    /**
     * @return void
     */
    public function makeDepositIntoAccount(): void
    {
        $inputedValues = input()->all();
        $validationResult = $this->isDepositDataValid($inputedValues);
        if ($validationResult === false) {
            $this->sendJsonMessage('Invalid inputted data.', 400);
        }

        $accountNumberDatabase = new AccountNumberDatabase();
        $selectAccountNumberResult = $accountNumberDatabase->selectByAccountNumber($inputedValues['accountNumber']);
        if ($selectAccountNumberResult === false) {
            $this->sendJsonMessage('Could not find the account.', 400);
        }

        $naturalPersonAccountId = $selectAccountNumberResult->getNaturalPersonAccountId();
        $legalPersonAccountId = $selectAccountNumberResult->getLegalPersonAccountId();

        if (is_numeric($naturalPersonAccountId) === true && is_null($legalPersonAccountId) === true) {
            $naturalPersonAccountService = new NaturalPersonAccountService();
            $account = $naturalPersonAccountService->generateNaturalPersonAccountObject(
                $inputedValues['accountNumber']
            );
            $this->makeTheDeposit(
                $account,
                $inputedValues['depositValue'],
                $naturalPersonAccountService,
                $naturalPersonAccountId
            );
        }
        if (is_numeric($legalPersonAccountId) === true && is_null($naturalPersonAccountId) === true) {
            $legalPersonAccountService = new legalPersonAccountService();
            $account = $legalPersonAccountService->generateLegalPersonAccountObject($inputedValues['accountNumber']);
            $this->makeTheDeposit(
                $account,
                $inputedValues['depositValue'],
                $legalPersonAccountService,
                $legalPersonAccountId
            );
        }

        $this->sendJsonMessage('An error occurred.', 400);
    }

    /**
     * @param array $inputedValues
     * @return bool
     */
    private function isDepositDataValid(array $inputedValues): bool
    {
        $requiredFields = [
            'accountNumber',
            'depositValue'
        ];

        $numberOfInputedFields = count($inputedValues);
        $numberOfRequiredFields = count($requiredFields);

        if ($numberOfInputedFields !== $numberOfRequiredFields) {
            $message = 'Error! Invalid number of fields. Please only enter the required fields: accountNumber and depositValue.';
            $this->sendJsonMessage($message, 400);
        }

        foreach ($requiredFields as $requiredField) {
            $isRequiredFieldInRequest = array_key_exists($requiredField, $inputedValues);
            if ($isRequiredFieldInRequest === false) {
                $message = 'Error! The field ' . $requiredField . ' does not exists in the payload.';
                $this->sendJsonMessage($message, 400);
            }
        }

        foreach ($inputedValues as $key => $field) {
            if (empty($field)) {
                $message = 'Error! The field ' . $key . ' is empty.';
                $this->sendJsonMessage($message, 400);
            }
            if (is_string($field) === false) {
                $message = 'Error! The field ' . $key . ' is not a string.';
                $this->sendJsonMessage($message, 400);
            }
        }

        $moneyRegex = '/^[0-9]{1,3}(\.[0-9]{3})*(,[0-9]{2})$/';
        $regexResult = preg_match($moneyRegex, $inputedValues['depositValue']);
        if ($regexResult === 0) {
            $message = 'Error! The field depositValue has a invalid money format. Use the following format: xxx.xxx,xx';
            $this->sendJsonMessage($message, 400);
        }

        $accountNumberDatabase = new AccountNumberDatabase();
        $selectAccountNumberResult = $accountNumberDatabase->selectByAccountNumber($inputedValues['accountNumber']);
        if ($selectAccountNumberResult === false) {
            $message = 'Error! The account number is invalid.';
            $this->sendJsonMessage($message, 400);
        }
        return true;
    }

    /**
     * @param NaturalPersonAccount|LegalPersonAccount $account
     * @param string $depositValueString
     * @param legalPersonAccountService| NaturalPersonAccountService $accountService
     * @param $accountId
     */
    private function makeTheDeposit(
        NaturalPersonAccount|LegalPersonAccount $account,
        string $depositValueString,
        NaturalPersonAccountService|legalPersonAccountService $accountService,
        $accountId
    ): void {
        $currencies = new ISOCurrencies();
        $numberFormatter = new \NumberFormatter('pt-BR', \NumberFormatter::DECIMAL);
        $moneyParser = new IntlLocalizedDecimalParser($numberFormatter, $currencies);

        $balance = $account->getBalance();
        $depositValue = $moneyParser->parse($depositValueString, new Currency('BRL'));

        $newBalance = $balance->add($depositValue);

        $accountService->updateBalance($newBalance->getAmount(), $accountId);

        $accountNumber = $account->getAccountNumber()->getAccountNumber();
        $message = 'Deposit made into the account: ' . $accountNumber . ' with value: ' . $depositValue->getAmount();

        $this->registerLog($message, 'transaction', 'deposit', Logger::INFO);

        $message = 'Deposit was made successfully!';
        $this->sendJsonMessage($message, 200);
    }
}

