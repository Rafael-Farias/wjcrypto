<?php

namespace WjCrypto\Controllers;

use WjCrypto\Models\Services\UserService;

class UsersController
{

    public function create(): void
    {
        $userService = new UserService();
        $validationReturn = $userService->validateNewUserData();

        if ($validationReturn !== null) {
            $this->sendJsonResponse($validationReturn['message'], $validationReturn['httpResponseCode']);
        }

        $insertResult = $userService->createUser();
        $this->sendJsonResponse($insertResult['message'], $insertResult['httpResponseCode']);
    }

    public function showUsers(): void
    {
        $userService = new UserService();
        $getAllUsersResult = $userService->getAllUsers();
        $this->sendJsonResponse($getAllUsersResult['message'], $getAllUsersResult['httpResponseCode']);
    }

    public function showUser(int $userId): void
    {
        var_dump($userId);
        die();
        $userService = new UserService();
        $getUserResult = $userService->getUser();
    }

    private function sendJsonResponse(array $dataArray, int $httpResponseCode): void
    {
        response()->httpCode($httpResponseCode);
        response()->json($dataArray);
    }

}
