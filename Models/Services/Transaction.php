<?php

namespace WjCrypto\Models\Services;

use WjCrypto\Helpers\ValidationHelper;
use WjCrypto\Models\Database\AccountNumberDatabase;
use WjCrypto\Models\Entities\LegalPersonAccount;
use WjCrypto\Models\Entities\NaturalPersonAccount;

class Transaction
{
    use ValidationHelper;

    protected NaturalPersonAccountService|LegalPersonAccountService $accountService;
    protected NaturalPersonAccount|LegalPersonAccount $account;

    /**
     * @param int $accountNumber
     */
    protected function createAccountObject(int $accountNumber): void
    {
        $this->validateAccountNumber($accountNumber);
        $accountNumberDatabase = new AccountNumberDatabase();
        $accountNumberObject = $accountNumberDatabase->selectByAccountNumber($accountNumber);

        $naturalPersonAccountId = $accountNumberObject->getNaturalPersonAccountId();
        $legalPersonAccountId = $accountNumberObject->getLegalPersonAccountId();

        if (is_numeric($naturalPersonAccountId) === true && is_null($legalPersonAccountId) === true) {
            $this->accountService = new NaturalPersonAccountService();
            $this->account = $this->accountService->generateNaturalPersonAccountObject($accountNumber);
        }

        if (is_numeric($legalPersonAccountId) === true && is_null($naturalPersonAccountId) === true) {
            $this->accountService = new LegalPersonAccountService();
            $this->account = $this->accountService->generateLegalPersonAccountObject($accountNumber);
        }
    }
}
