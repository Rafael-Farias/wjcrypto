<?php

namespace WjCrypto\Models\Services;

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\RFCValidation;
use Monolog\Logger;
use WjCrypto\Helpers\JsonResponse;
use WjCrypto\Helpers\LogHelper;
use WjCrypto\Helpers\ResponseArray;
use WjCrypto\Helpers\ValidationHelper;
use WjCrypto\Middlewares\AuthMiddleware;
use WjCrypto\Models\Database\AccountNumberDatabase;
use WjCrypto\Models\Database\UserDatabase;
use WjCrypto\Models\Entities\User;

class UserService
{
    use ResponseArray;
    use JsonResponse;
    use ValidationHelper;
    use LogHelper;

    /**
     *
     */
    public function createUser(): void
    {
        $newUserData = input()->all();
        $this->validateNewUserData($newUserData);

        $email = $newUserData['email'];
        $hashOptions = [
            'cost' => 15
        ];
        $hash = password_hash($newUserData['password'], PASSWORD_DEFAULT, $hashOptions);
        $userDatabase = new UserDatabase();
        $userDatabase->insert($email, $hash);

        $this->sendJsonMessage('User created successfully!', 201);
    }

    /**
     * @param array $newUserData
     */
    private function validateNewUserData(array $newUserData): void
    {
        $requiredFields = ['email', 'password'];

        $this->validateInput($requiredFields, $newUserData);

        $validator = new EmailValidator();
        $multipleValidations = new MultipleValidationWithAnd([
            new RFCValidation(),
            new DNSCheckValidation()
        ]);
        $email = input('email');
        if ($validator->isValid($email, $multipleValidations) === false) {
            $errorMessage = 'Error! Invalid email.';
            $this->sendJsonMessage($errorMessage, 400);
        }

        $userDatabase = new UserDatabase();
        $usersArray = $userDatabase->selectAll();
        if ($usersArray !== false) {
            foreach ($usersArray as $user) {
                if ($user->getEmail() === $email) {
                    $errorMessage = 'Error! The email ' . $email . ' is already in use.';
                    $this->sendJsonMessage($errorMessage, 400);
                }
            }
        }
    }

    /**
     * @return array
     */
    public function getAllUsers(): array
    {
        $userDatabase = new UserDatabase();
        $usersArray = $userDatabase->selectAll();
        if ($usersArray === false) {
            $this->sendJsonMessage('There is no registered user on the system.', 200);
        }
        $usersJsonArray = [];
        foreach ($usersArray as $user) {
            $usersJsonArray[] = $user->getUserData();
        }
        return $this->generateResponseArray($usersJsonArray, 200);
    }

    /**
     * @param int $userId
     * @return array
     */
    public function getUserData(int $userId): array
    {
        $this->validateUserId($userId);
        $userDatabase = new UserDatabase();
        $selectUserByIdResult = $userDatabase->selectById($userId);
        if ($selectUserByIdResult === false) {
            $errorMessage = 'Failed to retrieve the user with ID ' . $userId . ' from the database.';
            $this->sendJsonMessage($errorMessage, 400);
        }
        $userData = $selectUserByIdResult->getUserData();
        return $this->generateResponseArray($userData, 200);
    }

    /**
     * @param int $userId
     * @return User
     */
    public function getUser(int $userId): User
    {
        $this->validateUserId($userId);
        $userDatabase = new UserDatabase();
        $user = $userDatabase->selectById($userId);
        if ($user === false) {
            $errorMessage = 'Failed to retrieve the user with ID ' . $userId . ' from the database.';
            $this->sendJsonMessage($errorMessage, 400);
        }
        return $user;
    }

    /**
     * @param int $userId
     * @return bool
     */
    private function validateUserId(int $userId): bool
    {
        $userDatabase = new UserDatabase();
        $usersArray = $userDatabase->selectAll();
        if ($usersArray === false) {
            $this->sendJsonMessage('There is no registered user on the system.', 200);
        }
        foreach ($usersArray as $user) {
            if ($user->getId() === $userId) {
                return true;
            }
        }
        $errorMessage = 'Error! The User ID ' . $userId . ' does not exist in the database.';
        $this->sendJsonMessage($errorMessage, 400);
        return false;
    }

    /**
     * @param int $userId
     */
    public function deleteUser(int $userId): void
    {
        $this->validateUserId($userId);
        $userDatabase = new UserDatabase();

        $deleteResult = $userDatabase->delete($userId);
        if ($deleteResult === false) {
            $this->sendJsonMessage('Error! User could not be deleted.', 500);
        }
        $this->sendJsonMessage('User deleted successfully!', 200);
    }


    public function updateUser(): void
    {
        $authMiddleware = new AuthMiddleware();
        $userId = $authMiddleware->getUserId();

        $this->validateUserId($userId);
        $newUserData = input()->all();
        $this->validateNewUserData($newUserData);

        $email = $newUserData['email'];
        $hashOptions = [
            'cost' => 15
        ];
        $hash = password_hash($newUserData['password'], PASSWORD_DEFAULT, $hashOptions);
        $userDatabase = new UserDatabase();
        $insertResult = $userDatabase->update($email, $hash, $userId);
        if ($insertResult === false) {
            $this->sendJsonMessage('Error! Could not update the user.', 200);
        }
        $this->sendJsonMessage('User updated successfully!', 201);
    }

    /**
     * @param string $email
     * @param string $password
     * @return User
     */
    public function getUserByEmailAndPassword(string $email, string $password): User
    {
        $this->validateEmailAndPassword($email, $password);

        $userDatabase = new UserDatabase();
        $usersArray = $userDatabase->selectAll();
        if ($usersArray === false) {
            $this->sendJsonMessage('There is no user registered.', 200);
        }
        foreach ($usersArray as $user) {
            if ($user->getEmail() === $email) {
                $isPasswordCorrect = password_verify($password, $user->getPassword());
                if ($isPasswordCorrect) {
                    return $user;
                }
            }
        }
        $errorMessage = 'Error! The email ' . $email . ' is not registered in the system.';
        $this->sendJsonMessage($errorMessage, 400);
        exit(0);
    }

    /**
     * @param string $email
     * @param string $password
     */
    private function validateEmailAndPassword(string $email, string $password): void
    {
        if (is_string($email) === false || is_string($password) === false) {
            $errorMessage = 'Error! The fields must be string type.';
            $this->sendJsonMessage($errorMessage, 400);
        }

        if (empty($password) || empty($email)) {
            $errorMessage = 'Error! Invalid email or password.';
            $this->sendJsonMessage($errorMessage, 400);
        }

        $validator = new EmailValidator();
        $multipleValidations = new MultipleValidationWithAnd([
            new RFCValidation(),
            new DNSCheckValidation()
        ]);

        if ($validator->isValid($email, $multipleValidations) === false) {
            $errorMessage = 'Error! Invalid email or password.';
            $this->sendJsonMessage($errorMessage, 400);
        }
    }

    /**
     * @return int
     */
    public function getLoggedUserAccountNumber(): int
    {
        $authMiddleware = new AuthMiddleware();
        $userId = $authMiddleware->getUserId();

        $accountDatabase = new AccountNumberDatabase();
        $accountNumber = $accountDatabase->selectByUserId($userId);
        if ($accountNumber === false) {
            $message = 'Error! The logged user does not have a account.';
            $this->sendJsonMessage($message, 400);
        }
        return $accountNumber->getAccountNumber();
    }

    public function getLoggedUserAccountData(): array
    {
        $accountNumber = $this->getLoggedUserAccountNumber();
        $transaction = new Transaction();
        $account = $transaction->getLoggedUserAccount($accountNumber);
        $userId = $account->getAccountNumber()->getUserId();
        $message = 'User ' . $userId . ' requested the account data';
        $this->registerLog($message, 'resources', 'accountData', Logger::INFO);
        return $this->generateResponseArray($account->getAccountData(), 200);
    }

}