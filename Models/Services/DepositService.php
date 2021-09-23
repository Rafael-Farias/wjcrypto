<?php

namespace WjCrypto\Models\Services;

use Money\Money;
use Monolog\Logger;
use WjCrypto\Helpers\JsonResponse;
use WjCrypto\Helpers\LogHelper;
use WjCrypto\Helpers\MoneyHelper;
use WjCrypto\Helpers\ValidationHelper;

class DepositService extends Transaction
{
    use LogHelper;
    use JsonResponse;
    use ValidationHelper;
    use MoneyHelper;

    /**
     * @return void
     */
    public function makeDepositIntoAccount(): void
    {
        $inputedValues = input()->all();
        $this->validateDepositData($inputedValues);

        $this->createAccountObject($inputedValues['accountNumber']);

        $depositValue = $this->convertStringToMoney($inputedValues['depositValue']);

        $this->makeTheDeposit($depositValue);

        $this->sendJsonMessage('An error occurred.', 400);
    }

    /**
     * @param array $inputedValues
     * @return void
     */
    private function validateDepositData(array $inputedValues): void
    {
        $requiredFields = [
            'accountNumber',
            'depositValue'
        ];

        $this->validateInput($requiredFields, $inputedValues);
        $this->validateMoneyFormat($inputedValues['depositValue']);
        $this->validateAccountNumber($inputedValues['accountNumber']);
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
        $message = 'Deposit made into the account: ' . $accountNumber . ' with value: ' . $depositValue->getAmount();

        $this->registerLog($message, 'transaction', 'deposit', Logger::INFO);

        $this->sendJsonMessage('Deposit was made successfully!', 200);
    }
}
