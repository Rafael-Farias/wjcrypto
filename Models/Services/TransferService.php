<?php

namespace WjCrypto\Models\Services;

use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Money;
use Money\Parser\IntlLocalizedDecimalParser;
use WjCrypto\Helpers\CryptografyHelper;
use WjCrypto\Helpers\ResponseArray;
use WjCrypto\Models\Database\AccountNumberDatabase;
use WjCrypto\Models\Entities\LegalPersonAccount;
use WjCrypto\Models\Entities\NaturalPersonAccount;

class TransferService
{
    use CryptografyHelper;
    use ResponseArray;

    public function transfer(): array
    {
        $inputedValues = input()->all();
        $result = $this->validateTransferData($inputedValues);
        if (is_array($result)) {
            return $result;
        }
        $userService = new UserService();
        $loggedUserAccountNumber = $userService->getLoggedUserAccountNumber();
        $loggedUserAccount = $this->createAccountObject($loggedUserAccountNumber);
        $valueToTransfer = $this->parseMoneyObject($inputedValues['transferValue']);

        $transferValidationResult = $this->validateTransferAmount($loggedUserAccount['account'], $valueToTransfer);
        if (is_array($transferValidationResult)) {
            return $transferValidationResult;
        }
        $loggedUserBalance = $loggedUserAccount['account']->getBalance();
        $newLoggedAccountBalance = $loggedUserBalance->subtract($valueToTransfer);
        $updateBalanceResult = $this->updateAccountBalance(
            $loggedUserAccount['account'],
            $loggedUserAccount['accountService'],
            $newLoggedAccountBalance
        );
        if ($updateBalanceResult['httpResponseCode'] === 400) {
            return $updateBalanceResult;
        }

        $destinyAccount = $this->createAccountObject($inputedValues['accountNumber']);

        $destinyAccountBalance = $destinyAccount['account']->getBalance();
        $newDestinyAccountBalance = $destinyAccountBalance->add($valueToTransfer);
        return $this->updateAccountBalance(
            $destinyAccount['account'],
            $destinyAccount['accountService'],
            $newDestinyAccountBalance
        );
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
        $regexResult = preg_match($moneyRegex, $inputedValues['transferValue']);
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

        $destinyAccountNumber = $selectAccountNumberResult->getAccountNumber();
        $userService = new UserService();
        $loggedUserAccountNumber = $userService->getLoggedUserAccountNumber();

        if ($destinyAccountNumber === $loggedUserAccountNumber) {
            $message = 'Error! The destiny account must be different from the origin account.';
            return $this->generateResponseArray($message, 400);
        }

        return null;
    }

    private function createAccountObject(int $accountNumber): ?array
    {
        $accountNumberDatabase = new AccountNumberDatabase();
        $selectAccountNumberResult = $accountNumberDatabase->selectByAccountNumber($accountNumber);

        $naturalPersonAccountId = $selectAccountNumberResult->getNaturalPersonAccountId();
        $legalPersonAccountId = $selectAccountNumberResult->getLegalPersonAccountId();

        if (is_numeric($naturalPersonAccountId) === true && is_null($legalPersonAccountId) === true) {
            $accountService = new NaturalPersonAccountService();
            $account = $accountService->generateNaturalPersonAccountObject($accountNumber);
            return [
                'account' => $account,
                'accountService' => $accountService
            ];
        }
        if (is_numeric($legalPersonAccountId) === true && is_null($naturalPersonAccountId) === true) {
            $accountService = new legalPersonAccountService();
            $account = $accountService->generateLegalPersonAccountObject($accountNumber);
            return [
                'account' => $account,
                'accountService' => $accountService
            ];
        }
        return null;
    }

    /**
     * @param LegalPersonAccount|NaturalPersonAccount $account
     * @param Money $valueToTransfer
     * @return array|null
     */
    private function validateTransferAmount(
        LegalPersonAccount|NaturalPersonAccount $account,
        Money $valueToTransfer
    ): ?array {
        $balance = $account->getBalance();
        if ($balance->lessThan($valueToTransfer)) {
            $message = 'Invalid operation. The account does not have enough amount to make this transfer.';
            return $this->generateResponseArray($message, 400);
        }
        return null;
    }

    /**
     * @param NaturalPersonAccount|LegalPersonAccount $account
     * @param NaturalPersonAccountService|legalPersonAccountService $accountService
     * @param Money $newBalance
     * @return array
     */
    private function updateAccountBalance(
        NaturalPersonAccount|LegalPersonAccount $account,
        NaturalPersonAccountService|legalPersonAccountService $accountService,
        Money $newBalance
    ): array {
        $accountId = $account->getId();

        $result = $accountService->updateBalance($newBalance->getAmount(), $accountId);
        if ($result === false) {
            $message = 'Error!';
            return $this->generateResponseArray($message, 400);
        }
        $message = 'Success!';
        return $this->generateResponseArray($message, 200);
    }

    private function parseMoneyObject(string $value): Money
    {
        $currencies = new ISOCurrencies();
        $numberFormatter = new \NumberFormatter('pt-BR', \NumberFormatter::DECIMAL);
        $moneyParser = new IntlLocalizedDecimalParser($numberFormatter, $currencies);
        return $moneyParser->parse($value, new Currency('BRL'));
    }
}