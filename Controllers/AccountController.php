<?php

namespace WjCrypto\Controllers;

use WjCrypto\Helpers\JsonResponse;
use WjCrypto\Models\Services\legalPersonAccountService;
use WjCrypto\Models\Services\NaturalPersonAccountService;

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
        $legalPersonService = new legalPersonAccountService();
        $legalPersonService->createAccount();
    }
}