<?php

namespace WjCrypto\Models\Services;

use Money\Money;
use Monolog\Logger;
use WjCrypto\Helpers\JsonResponse;
use WjCrypto\Helpers\LogHelper;
use WjCrypto\Helpers\MoneyHelper;
use WjCrypto\Helpers\ValidationHelper;

class TransferService extends Transaction
{
    use JsonResponse;
    use ValidationHelper;
    use MoneyHelper;
    use LogHelper;

    /**
     *
     */
    public function transfer(): void
    {
        $inputedValues = input()->all();
        $this->validateTransferData($inputedValues);
        $valueToTransfer = $this->convertStringToMoney($inputedValues['transferValue']);

        $userService = new UserService();
        $loggedUserAccountNumber = $userService->getLoggedUserAccountNumber();

        $this->createAccountObject($loggedUserAccountNumber);

        $this->validateTransferAmount($valueToTransfer);

        $loggedUserBalance = $this->account->getBalance();
        $newLoggedAccountBalance = $loggedUserBalance->subtract($valueToTransfer);
        $this->updateAccountBalance($newLoggedAccountBalance);

        $this->createAccountObject($inputedValues['accountNumber']);

        $targetAccountBalance = $this->account->getBalance();
        $targetAccountNewBalance = $targetAccountBalance->add($valueToTransfer);
        $this->updateAccountBalance($targetAccountNewBalance);

        $message = 'Transfer made from account .' .
            $loggedUserAccountNumber .
            ' to account ' .
            $inputedValues['accountNumber'] .
            ' . Transfer value = ' .
            $valueToTransfer->getAmount() .
            ' .';
        $this->registerLog($message, 'transaction', 'transfer', Logger::INFO);

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

        $this->validateInput($requiredFields, $inputedValues);
        $this->validateMoneyFormat($inputedValues['transferValue']);
        $accountComparsionResult = $this->loggedAccountEqualsTargetAccount($inputedValues['accountNumber']);
        if ($accountComparsionResult === true) {
            $this->sendJsonMessage('Error! The destiny account must be different from the origin account.', 400);
        }
    }

    /**
     * @param Money $valueToTransfer
     */
    private function validateTransferAmount(Money $valueToTransfer): void
    {
        $balance = $this->account->getBalance();
        if ($balance->lessThan($valueToTransfer)) {
            $message = 'Invalid operation. The account ' . $this->account->getAccountNumber()->getAccountNumber() .
                ' does not have enough amount to make a withdraw.' .
                '. The account balance is: ' . $balance->getAmount() .
                ' the transfer value was: ' . $valueToTransfer->getAmount() . '.';
            $this->registerLog($message, 'transaction', 'transfer', Logger::INFO);

            $message = 'Invalid operation. The account does not have enough amount to make this transfer.';
            $this->sendJsonMessage($message, 400);
        }
    }

    /**
     * @param Money $newBalance
     */
    private function updateAccountBalance(Money $newBalance): void
    {
        $accountId = $this->account->getId();
        $this->accountService->updateBalance($newBalance->getAmount(), $accountId);
    }
}
