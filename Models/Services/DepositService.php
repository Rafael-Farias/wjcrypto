<?php

namespace WjCrypto\Models\Services;

use Money\Money;
use Monolog\Logger;
use WjCrypto\Helpers\CryptografyHelper;
use WjCrypto\Helpers\LogHelper;
use WjCrypto\Helpers\MoneyHelper;
use WjCrypto\Helpers\ValidationHelper;

class DepositService extends Transaction
{
    use LogHelper;
    use ValidationHelper;
    use MoneyHelper;
    use CryptografyHelper;

    /**
     * @return void
     */
    public function makeDepositIntoAccount(): void
    {
        $inputedValues = input()->all();
        $this->validateDepositData($inputedValues);

        $userService = new UserService();
        $loggedUserAccountNumber = $userService->getLoggedUserAccountNumber();
        $this->createAccountObject($loggedUserAccountNumber);

        $depositValue = $this->convertStringToMoney($inputedValues['depositValue']);

        $this->makeTheDeposit($depositValue);

        $message = 'Deposit to the account ' . $this->account->getAccountNumber()->getAccountNumber() . ' failed.' .
            '. The account balance is: ' . $this->getParsedBalance($this->account->getBalance()) .
            ' the deposit value was: ' . $this->getParsedBalance($depositValue) . '.';
        $this->registerLog($message, 'transaction', 'deposit', Logger::INFO);
        $this->sendJsonMessage('An error occurred.', 400);
    }

    /**
     * @param array $inputedValues
     * @return void
     */
    private function validateDepositData(array $inputedValues): void
    {
        $requiredFields = [
            'depositValue'
        ];

        $this->validateInput($requiredFields, $inputedValues);
        $this->validateMoneyFormat($inputedValues['depositValue']);
    }

    /**
     * @param Money $depositValue
     */
    private function makeTheDeposit(Money $depositValue): void
    {
        $balance = $this->account->getBalance();
        $newBalance = $balance->add($depositValue);

        $this->accountService->updateBalance($newBalance->getAmount(), $this->account->getId());

        $accountNumber = $this->account->getAccountNumber()->getAccountNumber();

        $message = 'Deposit made into the account: ' . $accountNumber .
            ' with value: ' . $this->getParsedBalance($depositValue);

        $transactionDataArray = [
            'operation' => 'Deposit',
            'date' => date('d/m/Y'),
            'value' => $this->getParsedBalance($depositValue),
            'originAccount' => $this->encrypt($accountNumber),
            'destinyAccount' => $this->encrypt($accountNumber)
        ];

        $this->registerLog($message, 'transaction', 'deposit', Logger::INFO, $transactionDataArray);

        $this->sendJsonMessage('Deposit was made successfully!', 200);
    }
}
