<?php

namespace WjCrypto\Models\Services;

use Money\Money;
use WjCrypto\Helpers\CryptografyHelper;
use WjCrypto\Helpers\JsonResponse;
use WjCrypto\Helpers\MoneyHelper;
use WjCrypto\Helpers\ResponseArray;
use WjCrypto\Helpers\ValidationHelper;
use WjCrypto\Models\Entities\LegalPersonAccount;
use WjCrypto\Models\Entities\NaturalPersonAccount;

class TransferService extends Transaction
{
    use CryptografyHelper;
    use ResponseArray;
    use JsonResponse;
    use ValidationHelper;
    use MoneyHelper;

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

        $this->validateTransferAmount($this->account, $valueToTransfer);

        $loggedUserBalance = $this->account->getBalance();
        $newLoggedAccountBalance = $loggedUserBalance->subtract($valueToTransfer);
        $this->updateAccountBalance($newLoggedAccountBalance);

        $this->createAccountObject($inputedValues['accountNumber']);

        $targetAccountBalance = $this->account->getBalance();
        $targetAccountNewBalance = $targetAccountBalance->add($valueToTransfer);
        $this->updateAccountBalance($targetAccountNewBalance);

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
     * @param Money $newBalance
     */
    private function updateAccountBalance(Money $newBalance): void
    {
        $accountId = $this->account->getId();
        $this->accountService->updateBalance($newBalance->getAmount(), $accountId);
    }
}