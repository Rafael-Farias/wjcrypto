<?php

namespace WjCrypto\Models\Services;

use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Money;
use Money\Parser\IntlLocalizedDecimalParser;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use WjCrypto\Helpers\ResponseArray;
use WjCrypto\Models\Database\AccountNumberDatabase;
use WjCrypto\Models\Entities\LegalPersonAccount;
use WjCrypto\Models\Entities\NaturalPersonAccount;

class WithdrawService
{
    use ResponseArray;

    private $accountService;

    public function withdrawFromAccount()
    {
        $inputedValues = input()->all();
        $validationResult = $this->validateWithdrawData($inputedValues);
        if (is_array($validationResult)) {
            return $validationResult;
        }
        $account = $this->createAccountObject($inputedValues);
        if (is_null($account)) {
            $message = 'Error! Could not create the account object.';
            return $this->generateResponseArray($message, 400);
        }
        $withdrawValidation = $this->validateWithdraw($account, $inputedValues['withdrawValue']);
        if (is_array($withdrawValidation)) {
            return $withdrawValidation;
        }
        return $this->makeTheWithdraw($account, $withdrawValidation);
    }

    private function validateWithdrawData(array $inputedValues): ?array
    {
        $requiredFields = [
            'accountNumber',
            'withdrawValue'
        ];

        $numberOfInputedFields = count($inputedValues);
        $numberOfRequiredFields = count($requiredFields);

        if ($numberOfInputedFields !== $numberOfRequiredFields) {
            $message = 'Error! Invalid number of fields. Please only enter the required fields: accountNumber and withdrawValue.';
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
            $message = 'Error! The field withdrawValue has a invalid money format. Use the following format: xxx.xxx,xx';
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

    private function createAccountObject(array $inputedValues)
    {
        $accountNumberDatabase = new AccountNumberDatabase();
        $selectAccountNumberResult = $accountNumberDatabase->selectByAccountNumber($inputedValues['accountNumber']);

        $naturalPersonAccountId = $selectAccountNumberResult->getNaturalPersonAccountId();
        $legalPersonAccountId = $selectAccountNumberResult->getLegalPersonAccountId();

        if (is_numeric($naturalPersonAccountId) === true && is_null($legalPersonAccountId) === true) {
            $this->accountService = new NaturalPersonAccountService();
            return $this->accountService->generateNaturalPersonAccountObject(
                $inputedValues['accountNumber']
            );
        }
        if (is_numeric($legalPersonAccountId) === true && is_null($naturalPersonAccountId) === true) {
            $this->accountService = new legalPersonAccountService();
            return $this->accountService->generateLegalPersonAccountObject($inputedValues['accountNumber']);
        }

        return null;
    }

    /**
     * @param $account
     * @param $withdrawValue1
     */
    private function validateWithdraw($account, $withdrawValue1)
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
            $message = 'Invalid operation. The account does not have enough amount to make this withdraw.';
            return $this->generateResponseArray($message, 400);
        }

        return $balance->subtract($withdrawValue);
    }

    private function makeTheWithdraw($account, Money $newBalance): array
    {
        /**
         * @var NaturalPersonAccount | LegalPersonAccount $account
         */
        $accountId = $account->getAccountNumber()->getId();

        $result = $this->accountService->updateBalance($newBalance->getAmount(), $accountId);
        if ($result === false) {
            $message = 'Error!';
            return $this->generateResponseArray($message, 400);
        }
        $message = 'Success!';
        $this->registerLog(
            'Withdraw made from account ' . $account->getAccountNumber()->getAccountNumber(
            ) . ' with new balance ' . $newBalance->getAmount() . '.'
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