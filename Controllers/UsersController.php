<?php

namespace WjCrypto\Controllers;

use WjCrypto\Models\Services\UserService;

class UsersController
{

    public function create(): void
    {
        $userService = new UserService();
        $validationReturn = $userService->validateUserData();

        if ($validationReturn !== null) {
            $this->sendJsonResponse($validationReturn['message'], $validationReturn['httpResponseCode']);
        }

        $insertResult = $userService->createUser();
        $this->sendJsonResponse($insertResult['message'], $insertResult['httpResponseCode']);
    }

    public function showAll(): void
    {
        $userService = new UserService();
        $getAllUsersResult = $userService->getAllUsers();
        $this->sendJsonResponse($getAllUsersResult['message'], $getAllUsersResult['httpResponseCode']);
    }

    public function show(int $userId): void
    {
        $userService = new UserService();
        $validationReturn = $userService->validateUserId($userId);
        if ($validationReturn !== null) {
            $this->sendJsonResponse($validationReturn['message'], $validationReturn['httpResponseCode']);
        }

        $getUserResult = $userService->getUser($userId);
        $userData = $getUserResult['message']->getUserData();
        $this->sendJsonResponse($userData, $getUserResult['httpResponseCode']);
    }

    public function delete(int $userId): void
    {
        $userService = new UserService();
        $validationReturn = $userService->validateUserId($userId);
        if ($validationReturn !== null) {
            $this->sendJsonResponse($validationReturn['message'], $validationReturn['httpResponseCode']);
        }

        $deleteUserResult = $userService->deleteUser($userId);
        $this->sendJsonResponse($deleteUserResult['message'], $deleteUserResult['httpResponseCode']);
    }

    public function update(int $userId): void
    {
        $userService = new UserService();
        $validationReturn = $userService->validateUserId($userId);
        if ($validationReturn !== null) {
            $this->sendJsonResponse($validationReturn['message'], $validationReturn['httpResponseCode']);
        }
        $validationReturn = $userService->validateUserData();
        if ($validationReturn !== null) {
            $this->sendJsonResponse($validationReturn['message'], $validationReturn['httpResponseCode']);
        }
        $updateResult = $userService->updateUser($userId);
        $this->sendJsonResponse($updateResult['message'], $updateResult['httpResponseCode']);
    }

    private function sendJsonResponse(array $dataArray, int $httpResponseCode): void
    {
        response()->httpCode($httpResponseCode);
        response()->json($dataArray);
    }

}
