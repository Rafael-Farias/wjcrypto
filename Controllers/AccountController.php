<?php

namespace WjCrypto\Controllers;

use WjCrypto\Models\Services\NaturalPersonAccountService;

class AccountController
{
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
    }

    private function sendJsonResponse(array $dataArray, int $httpResponseCode): void
    {
        response()->httpCode($httpResponseCode);
        response()->json($dataArray);
    }

}