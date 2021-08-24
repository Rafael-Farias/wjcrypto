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
        $userService = new UserService();
        $validationReturn = $userService->validateUserId($userId);
        if ($validationReturn !== null) {
            $this->sendJsonResponse($validationReturn['message'], $validationReturn['httpResponseCode']);
        }

        $getUserResult = $userService->getUser($userId);
        $this->sendJsonResponse($getUserResult['message'], $getUserResult['httpResponseCode']);
    }

    public function deleteUser(int $userId): void
    {
        $userService = new UserService();
        $validationReturn = $userService->validateUserId($userId);
        if ($validationReturn !== null) {
            $this->sendJsonResponse($validationReturn['message'], $validationReturn['httpResponseCode']);
        }

        $deleteUserResult = $userService->deleteUser($userId);
        $this->sendJsonResponse($deleteUserResult['message'], $deleteUserResult['httpResponseCode']);
    }

    private function sendJsonResponse(array $dataArray, int $httpResponseCode): void
    {
        response()->httpCode($httpResponseCode);
        response()->json($dataArray);
    }

}
