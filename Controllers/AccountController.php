<?php

namespace WjCrypto\Controllers;

use WjCrypto\Helpers\JsonResponse;
use WjCrypto\Models\Services\LegalPersonAccountService;
use WjCrypto\Models\Services\NaturalPersonAccountService;
use WjCrypto\Models\Services\UserService;

class AccountController
{
    use JsonResponse;

    public function createNaturalPersonAccount()
    {
        $naturalPersonService = new NaturalPersonAccountService();
        $naturalPersonService->createAccount();
    }

    public function createLegalPersonAccount()
    {
        $legalPersonService = new LegalPersonAccountService();
        $legalPersonService->createAccount();
    }

    public function getAccountData()
    {
        $userService = new UserService();
        $accountData = $userService->getLoggedUserAccountData();
        $this->sendJsonResponse($accountData['message'], $accountData['httpResponseCode']);
    }
}
