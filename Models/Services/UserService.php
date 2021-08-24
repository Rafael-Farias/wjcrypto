<?php

namespace WjCrypto\Models\Services;

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\RFCValidation;
use WjCrypto\Models\Database\UserDatabase;

class UserService
{
    public function createUser(): array
    {
        $newUserData = input()->all();

        $email = $newUserData['email'];
        $hashOptions = [
            'cost' => 15
        ];
        $hash = password_hash($newUserData['password'], PASSWORD_DEFAULT, $hashOptions);
        $userDatabase = new UserDatabase();
        $insertResult = $userDatabase->insert($email, $hash);
        if (is_string($insertResult)) {
            return $this->returnResponseArray($insertResult, 500);
        }
        $message = 'User created successfully!';
        return $this->returnResponseArray($message, 201);
    }

    public function validateNewUserData(): ?array
    {
        $requiredFields = ['email', 'password'];
        if (input()->exists($requiredFields) === false) {
            $errorMessage = 'Error! One or more missing fields.';
            return $this->returnResponseArray($errorMessage, 400);
        }
        $validator = new EmailValidator();
        $multipleValidations = new MultipleValidationWithAnd([
                                                                 new RFCValidation(),
                                                                 new DNSCheckValidation()
                                                             ]);
        $email = input('email');

        if ($validator->isValid($email, $multipleValidations) === false) {
            $errorMessage = 'Error! Invalid email.';
            return $this->returnResponseArray($errorMessage, 400);
        }

        $userDatabase = new UserDatabase();
        $usersArray = $userDatabase->selectAll();
        foreach ($usersArray as $user) {
            if ($user->getEmail() === $email) {
                $errorMessage = 'Error! The email ' . $email . ' is already in use.';
                return $this->returnResponseArray($errorMessage, 400);
            }
        }
        return null;
    }

    public function getAllUsers(): array
    {
        $userDatabase = new UserDatabase();
        $usersArray = $userDatabase->selectAll();
        $usersJsonArray = [];
        foreach ($usersArray as $user) {
            $usersJsonArray[] = $user->getUserData();
        }
        return $this->returnResponseArray($usersJsonArray, 200);
    }

    public function getUser(int $userId): array
    {
        $userDatabase = new UserDatabase();
        $selectUserByIdResult = $userDatabase->selectById($userId);
        if (is_string($selectUserByIdResult)) {
            return $this->returnResponseArray($selectUserByIdResult, 400);
        }
        if (is_bool($selectUserByIdResult)) {
            $errorMessage = 'Failed to retrieve the user with ID ' . $userId . ' from the database.';
            return $this->returnResponseArray($errorMessage, 400);
        }
        $userData = $selectUserByIdResult->getUserData();
        return $this->returnResponseArray($userData, 200);
    }

    public function validateUserId(int $userId): ?array
    {
        $userDatabase = new UserDatabase();
        $usersArray = $userDatabase->selectAll();
        foreach ($usersArray as $user) {
            if ($user->getId() === $userId) {
                return null;
            }
        }
        $errorMessage = 'Error! The User ID ' . $userId . ' does not exist in the database.';
        return $this->returnResponseArray($errorMessage, 400);
    }

    public function deleteUser(int $userId): array
    {
        $userDatabase = new UserDatabase();

        $deleteResult = $userDatabase->delete($userId);
        if (is_string($deleteResult)) {
            return $this->returnResponseArray($deleteResult, 400);
        }

        $message = 'User deleted successfully!';
        return $this->returnResponseArray($message, 200);
    }

    private function returnResponseArray($message, int $httpResponseCode): array
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