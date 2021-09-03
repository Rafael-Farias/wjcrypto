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
        $createResult = $naturalPersonService->createAccount();
        if (is_array($createResult)) {
            $this->sendJsonResponse($createResult['message'], $createResult['httpResponseCode']);
        }
    }

    public function createLegalPersonAccount()
    {
        $legalPersonService = new legalPersonAccountService();
        $createResult = $legalPersonService->createAccount();
        if (is_array($createResult)) {
            $this->sendJsonResponse($createResult['message'], $createResult['httpResponseCode']);
        }
    }
}