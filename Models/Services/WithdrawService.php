<?php

namespace WjCrypto\Models\Services;

use Money\Money;
use Monolog\Logger;
use WjCrypto\Helpers\CryptografyHelper;
use WjCrypto\Helpers\JsonResponse;
use WjCrypto\Helpers\LogHelper;
use WjCrypto\Helpers\MoneyHelper;
use WjCrypto\Helpers\ValidationHelper;

class WithdrawService extends Transaction
{
    use ValidationHelper;
    use LogHelper;
    use MoneyHelper;
    use JsonResponse;
    use CryptografyHelper;

    public function withdrawFromAccount()
    {
        $inputedValues = input()->all();
        $this->validateWithdrawData($inputedValues);

        $userService = new UserService();
        $loggedUserAccountNumber = $userService->getLoggedUserAccountNumber();
        $this->createAccountObject($loggedUserAccountNumber);

        $withdrawValue = $this->convertStringToMoney($inputedValues['withdrawValue']);

        $this->validateWithdraw($withdrawValue);

        $this->makeTheWithdraw($withdrawValue);
    }

    private function validateWithdrawData(array $inputedValues): void
    {
        $requiredFields = [
            'withdrawValue'
        ];

        $this->validateInput($requiredFields, $inputedValues);
        $this->validateMoneyFormat($inputedValues['withdrawValue']);
    }

    /**
     * @param Money $withdrawValue
     */
    private function validateWithdraw(Money $withdrawValue): void
    {
        $balance = $this->account->getBalance();

        if ($balance->lessThan($withdrawValue)) {
            $message = 'Invalid operation. The account ' . $this->account->getAccountNumber()->getAccountNumber() .
                ' does not have enough amount to make a withdraw.' .
                '. The account balance is: ' . $this->getParsedBalance($balance) .
                ' the withdraw value was: ' . $this->getParsedBalance($withdrawValue) . '.';
            $this->registerLog($message, 'transaction', 'withdraw', Logger::INFO);
            $this->sendJsonMessage('Invalid operation. Non-sufficient funds.', 400);
        }
    }

    /**
     * @param Money $withdrawValue
     */
    private function makeTheWithdraw(Money $withdrawValue): void
    {
        $accountId = $this->account->getId();
        $accountBalance = $this->account->getBalance();
        $newBalance = $accountBalance->subtract($withdrawValue);

        $this->accountService->updateBalance($newBalance->getAmount(), $accountId);

        $message = 'Withdraw made from account ' . $this->account->getAccountNumber()->getAccountNumber() .
            '. Before the transaction the balance was: ' . $this->getParsedBalance($accountBalance) .
            ' now the account balance is: ' . $this->getParsedBalance($newBalance) . '.';

        $transactionDataArray = [
            'operation' => 'Withdraw',
            'date' => date('d/m/Y'),
            'value' => $this->getParsedBalance($withdrawValue),
            'originAccount' => $this->encrypt($this->account->getAccountNumber()->getAccountNumber()),
            'destinyAccount' => $this->encrypt($this->account->getAccountNumber()->getAccountNumber())
        ];

        $this->registerLog($message, 'transaction', 'withdraw', Logger::INFO, $transactionDataArray);
        $this->sendJsonMessage('Success!', 200);
    }
}
