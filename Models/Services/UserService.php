<?php

namespace WjCrypto\Models\Services;

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\RFCValidation;
use WjCrypto\Helpers\ResponseArray;
use WjCrypto\Middlewares\AuthMiddleware;
use WjCrypto\Models\Database\AccountNumberDatabase;
use WjCrypto\Models\Database\UserDatabase;
use WjCrypto\Models\Entities\User;

class UserService
{
    use ResponseArray;

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
            return $this->generateResponseArray($insertResult, 500);
        }
        $message = 'User created successfully!';
        return $this->generateResponseArray($message, 201);
    }

    public function validateUserData(): ?array
    {
        $requiredFields = ['email', 'password'];
        if (input()->exists($requiredFields) === false) {
            $errorMessage = 'Error! One or more missing fields.';
            return $this->generateResponseArray($errorMessage, 400);
        }
        $validator = new EmailValidator();
        $multipleValidations = new MultipleValidationWithAnd([
            new RFCValidation(),
            new DNSCheckValidation()
        ]);
        $email = input('email');
        if ($validator->isValid($email, $multipleValidations) === false) {
            $errorMessage = 'Error! Invalid email.';
            return $this->generateResponseArray($errorMessage, 400);
        }

        $userDatabase = new UserDatabase();
        $usersArray = $userDatabase->selectAll();
        foreach ($usersArray as $user) {
            if ($user->getEmail() === $email) {
                $errorMessage = 'Error! The email ' . $email . ' is already in use.';
                return $this->generateResponseArray($errorMessage, 400);
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
        return $this->generateResponseArray($usersJsonArray, 200);
    }

    public function getUser(int $userId): array
    {
        $userDatabase = new UserDatabase();
        $selectUserByIdResult = $userDatabase->selectById($userId);
        if (is_string($selectUserByIdResult)) {
            return $this->generateResponseArray($selectUserByIdResult, 400);
        }
        if (is_bool($selectUserByIdResult)) {
            $errorMessage = 'Failed to retrieve the user with ID ' . $userId . ' from the database.';
            return $this->generateResponseArray($errorMessage, 400);
        }
        return $this->generateResponseArray($selectUserByIdResult, 200);
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
        return $this->generateResponseArray($errorMessage, 400);
    }

    public function deleteUser(int $userId): array
    {
        $userDatabase = new UserDatabase();

        $deleteResult = $userDatabase->delete($userId);
        if (is_string($deleteResult)) {
            return $this->generateResponseArray($deleteResult, 400);
        }

        $message = 'User deleted successfully!';
        return $this->generateResponseArray($message, 200);
    }

    public function updateUser(int $userId): array
    {
        $newUserData = input()->all();

        $email = $newUserData['email'];
        $hashOptions = [
            'cost' => 15
        ];
        $hash = password_hash($newUserData['password'], PASSWORD_DEFAULT, $hashOptions);
        $userDatabase = new UserDatabase();
        $insertResult = $userDatabase->update($email, $hash, $userId);
        if (is_string($insertResult)) {
            return $this->generateResponseArray($insertResult, 500);
        }
        $message = 'User updated successfully!';
        return $this->generateResponseArray($message, 201);
    }

    /**
     * @return array|User
     */
    public function validateEmailAndPasswordThenMatchesPersistedUser(): User|array
    {
        $email = $_SERVER['PHP_AUTH_USER'];
        $password = $_SERVER['PHP_AUTH_PW'];

        if (is_null($password)) {
            $errorMessage = 'Error! Invalid email or password.';
            return $this->generateResponseArray($errorMessage, 400);
        }

        $validator = new EmailValidator();
        $multipleValidations = new MultipleValidationWithAnd([
            new RFCValidation(),
            new DNSCheckValidation()
        ]);

        if ($validator->isValid($email, $multipleValidations) === false) {
            $errorMessage = 'Error! Invalid email or password.';
            return $this->generateResponseArray($errorMessage, 400);
        }

        $userDatabase = new UserDatabase();
        $usersArray = $userDatabase->selectAll();
        foreach ($usersArray as $user) {
            if ($user->getEmail() === $email) {
                $isPasswordCorrect = password_verify($password, $user->getPassword());
                if ($isPasswordCorrect) {
                    return $user;
                }
            }
        }
        $errorMessage = 'Error! The email ' . $email . ' is not registered in the system.';
        return $this->generateResponseArray($errorMessage, 400);
    }

    public function getLoggedUserAccountNumber(): int
    {
        $authMiddleware = new AuthMiddleware();
        $userId = $authMiddleware->getUserId();

        $accountDatabase = new AccountNumberDatabase();
        return $accountDatabase->selectByUserId($userId)->getAccountNumber();
    }

}