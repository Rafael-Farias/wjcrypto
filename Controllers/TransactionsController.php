<?php

namespace WjCrypto\Controllers;

use WjCrypto\Helpers\JsonResponse;
use WjCrypto\Models\Services\DepositService;

class TransactionsController
{
    use JsonResponse;

    public function deposit()
    {
        $depositService = new DepositService();
        $depositResult = $depositService->makeDepositIntoAccount();
        if (is_array($depositResult)) {
            $this->sendJsonResponse($depositResult['message'], $depositResult['httpResponseCode']);
        }
    }
}