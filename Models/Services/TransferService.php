<?php

namespace WjCrypto\Models\Services;

use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Money;
use Money\Parser\IntlLocalizedDecimalParser;
use WjCrypto\Helpers\CryptografyHelper;
use WjCrypto\Helpers\JsonResponse;
use WjCrypto\Helpers\ResponseArray;
use WjCrypto\Models\Database\AccountNumberDatabase;
use WjCrypto\Models\Entities\LegalPersonAccount;
use WjCrypto\Models\Entities\NaturalPersonAccount;

class TransferService
{
    use CryptografyHelper;
    use ResponseArray;
    use JsonResponse;

    /**
     *
     */
    public function transfer(): void
    {
        $inputedValues = input()->all();
        $this->validateTransferData($inputedValues);

        $userService = new UserService();
        $loggedUserAccountNumber = $userService->getLoggedUserAccountNumber();

        $loggedUserAccount = $this->createAccountObject($loggedUserAccountNumber);
        $valueToTransfer = $this->parseMoneyObject($inputedValues['transferValue']);

        $this->validateTransferAmount($loggedUserAccount['account'], $valueToTransfer);

        $loggedUserBalance = $loggedUserAccount['account']->getBalance();
        $newLoggedAccountBalance = $loggedUserBalance->subtract($valueToTransfer);
        $this->updateAccountBalance(
            $loggedUserAccount['account'],
            $loggedUserAccount['accountService'],
            $newLoggedAccountBalance
        );

        $destinyAccount = $this->createAccountObject($inputedValues['accountNumber']);

        $destinyAccountBalance = $destinyAccount['account']->getBalance();
        $newDestinyAccountBalance = $destinyAccountBalance->add($valueToTransfer);
        $this->updateAccountBalance(
            $destinyAccount['account'],
            $destinyAccount['accountService'],
            $newDestinyAccountBalance
        );
        $this->sendJsonMessage('Transfer executed successfully!', 200);
    }

    /**
     * @param array $inputedValues
     */
    private function validateTransferData(array $inputedValues): void
    {
        $requiredFields = [
            'accountNumber',
            'transferValue'
        ];

        $numberOfInputedFields = count($inputedValues);
        $numberOfRequiredFields = count($requiredFields);

        if ($numberOfInputedFields !== $numberOfRequiredFields) {
            $message = 'Error! Invalid number of fields. Please only enter the required fields: accountNumber and transferValue.';
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
        $regexResult = preg_match($moneyRegex, $inputedValues['transferValue']);
        if ($regexResult === 0) {
            $message = 'Error! The field transferValue has a invalid money format. Use the following format: xxx.xxx,xx';
            $this->sendJsonMessage($message, 400);
        }

        $accountNumberDatabase = new AccountNumberDatabase();
        $selectAccountNumberResult = $accountNumberDatabase->selectByAccountNumber($inputedValues['accountNumber']);
        if ($selectAccountNumberResult === false) {
            $message = 'Error! The account number is invalid.';
            $this->sendJsonMessage($message, 400);
        }

        $destinyAccountNumber = $selectAccountNumberResult->getAccountNumber();
        $userService = new UserService();
        $loggedUserAccountNumber = $userService->getLoggedUserAccountNumber();

        if ($destinyAccountNumber === $loggedUserAccountNumber) {
            $message = 'Error! The destiny account must be different from the origin account.';
            $this->sendJsonMessage($message, 400);
        }
    }

    /**
     * @param int $accountNumber
     * @return array
     */
    private function createAccountObject(int $accountNumber): array
    {
        $accountNumberDatabase = new AccountNumberDatabase();
        $selectAccountNumberResult = $accountNumberDatabase->selectByAccountNumber($accountNumber);
        if ($selectAccountNumberResult === false) {
            $message = 'Error! The account number is invalid.';
            $this->sendJsonMessage($message, 400);
        }

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

        $accountService = new legalPersonAccountService();
        $account = $accountService->generateLegalPersonAccountObject($accountNumber);
        return [
            'account' => $account,
            'accountService' => $accountService
        ];
    }

    /**
     * @param LegalPersonAccount|NaturalPersonAccount $account
     * @param Money $valueToTransfer
     */
    private function validateTransferAmount(
        LegalPersonAccount|NaturalPersonAccount $account,
        Money $valueToTransfer
    ): void {
        $balance = $account->getBalance();
        if ($balance->lessThan($valueToTransfer)) {
            $message = 'Invalid operation. The account does not have enough amount to make this transfer.';
            $this->sendJsonMessage($message, 400);
        }
    }

    /**
     * @param NaturalPersonAccount|LegalPersonAccount $account
     * @param NaturalPersonAccountService|legalPersonAccountService $accountService
     * @param Money $newBalance
     */
    private function updateAccountBalance(
        NaturalPersonAccount|LegalPersonAccount $account,
        NaturalPersonAccountService|legalPersonAccountService $accountService,
        Money $newBalance
    ): void {
        $accountId = $account->getId();

        $accountService->updateBalance($newBalance->getAmount(), $accountId);
    }

    private function parseMoneyObject(string $value): Money
    {
        $currencies = new ISOCurrencies();
        $numberFormatter = new \NumberFormatter('pt-BR', \NumberFormatter::DECIMAL);
        $moneyParser = new IntlLocalizedDecimalParser($numberFormatter, $currencies);
        return $moneyParser->parse($value, new Currency('BRL'));
    }
}