<?php

namespace WjCrypto\Helpers;

trait ResponseArray
{
    public function generateResponseArray($message, int $httpResponseCode): array
    {
        if (is_string($message)) {
            $messageArray = ['message' => $message];
            return [
                'message' => $messageArray,
                'httpResponseCode' => $httpResponseCode
            ];
        }
        return [
            'message' => $message,
            'httpResponseCode' => $httpResponseCode
        ];
    }
}