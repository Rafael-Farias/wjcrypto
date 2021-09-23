<?php

namespace WjCrypto\Controllers;

use WjCrypto\Helpers\JsonResponse;
use WjCrypto\Models\Services\DepositService;
use WjCrypto\Models\Services\TransferService;
use WjCrypto\Models\Services\WithdrawService;

class TransactionsController
{
    use JsonResponse;

    public function deposit()
    {
        $depositService = new DepositService();
        $depositService->makeDepositIntoAccount();
    }

    public function withdraw()
    {
        $withdrawService = new WithdrawService();
        $withdrawResult = $withdrawService->withdrawFromAccount();
        if (is_array($withdrawResult)) {
            $this->sendJsonResponse($withdrawResult['message'], $withdrawResult['httpResponseCode']);
        }
    }

    public function transfer()
    {
        $transferService = new TransferService();
        $transferService->transfer();
    }
}