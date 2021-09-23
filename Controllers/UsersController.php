<?php

namespace WjCrypto\Controllers;

use WjCrypto\Helpers\JsonResponse;
use WjCrypto\Models\Services\UserService;

class UsersController
{
    use JsonResponse;

    public function create(): void
    {
        $userService = new UserService();
        $userService->createUser();
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
        $getUserResult = $userService->getUserData($userId);
        $this->sendJsonResponse($getUserResult['message'], $getUserResult['httpResponseCode']);
    }

    public function delete(int $userId): void
    {
        $userService = new UserService();
        $userService->deleteUser($userId);
    }

    public function update(int $userId): void
    {
        $userService = new UserService();
        $userService->updateUser($userId);
    }


}
