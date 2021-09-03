<?php

namespace WjCrypto\Helpers;

trait JsonResponse
{
    private function sendJsonResponse(array $dataArray, int $httpResponseCode): void
    {
        response()->httpCode($httpResponseCode);
        response()->json($dataArray);
    }
}