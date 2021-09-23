<?php

namespace WjCrypto\Models\Database;

use Monolog\Logger;
use PDO;
use WjCrypto\Helpers\CryptografyHelper;
use WjCrypto\Helpers\JsonResponse;
use WjCrypto\Helpers\LogHelper;
use WjCrypto\Helpers\ResponseArray;
use WjCrypto\Models\Entities\User;

class UserDatabase extends Database
{
    use CryptografyHelper;
    use LogHelper;
    use ResponseArray;
    use JsonResponse;


    private PDO $connection;

    public function __construct()
    {
        $this->connection = static::getConnection();
    }

    /**
     * @param string $email
     * @param string $password
     * @return bool
     */
    public function insert(string $email, string $password): bool
    {
        $encryptedEmail = $this->encrypt($email);
        $encryptedPassword = $this->encrypt($password);
        try {
            $sqlQuery = "INSERT INTO users (`email`, `password`) VALUES (:email, :password);";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':email', $encryptedEmail);
            $statement->bindParam(':password', $encryptedPassword);
            return $statement->execute();
        } catch (\PDOException $exception) {
            $message = 'PDO error on method WjCrypto\Models\Database\UserDatabase\insert: ' . $exception->getMessage();
            $this->registerLog($message, 'database', 'UserDatabase', Logger::ERROR);
            $return = $this->generateResponseArray(
                'An error occurred while processing your request. Contact the system administrator.',
                500
            );
            $this->sendJsonResponse($return['message'], $return['httpResponseCode']);
        }
        return false;
    }

    /**
     * @return User[]|bool
     */
    public function selectAll(): array|bool
    {
        try {
            $resultArray = [];
            $sqlQuery = "SELECT * FROM users;";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->execute();
            $statement->setFetchMode(PDO::FETCH_ASSOC);
            $queryReturn = $statement->fetchAll();
            if (empty($queryReturn)) {
                return false;
            }
            foreach ($queryReturn as $userAssociativeArray) {
                $decryptedArray = $this->decryptArray($userAssociativeArray);
                $user = $this->createUserObject($decryptedArray);
                $resultArray[] = $user;
            }
            return $resultArray;
        } catch (\PDOException $exception) {
            $message = 'PDO error on method WjCrypto\Models\Database\UserDatabase\selectAll: ' . $exception->getMessage(
                );
            $this->registerLog($message, 'database', 'UserDatabase', Logger::ERROR);
            $return = $this->generateResponseArray(
                'An error occurred while processing your request. Contact the system administrator.',
                500
            );
            $this->sendJsonResponse($return['message'], $return['httpResponseCode']);
        }
        return false;
    }

    /**
     * @param int $userId
     * @return User|bool
     */
    public function selectById(int $userId): User|bool
    {
        try {
            $sqlQuery = "SELECT `id`,`email`,`creation_timestamp`,`update_timestamp` FROM users WHERE id=:id;";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':id', $userId, PDO::PARAM_INT);
            $statement->execute();
            $statement->setFetchMode(PDO::FETCH_ASSOC);
            $row = $statement->fetch();
            if ($row === false) {
                return false;
            }
            $decryptedArray = $this->decryptArray($row);
            return $this->createUserObject($decryptedArray);
        } catch (\PDOException $exception) {
            $message = 'PDO error on method WjCrypto\Models\Database\UserDatabase\selectById: ' . $exception->getMessage();
            $this->registerLog($message, 'database', 'UserDatabase', Logger::ERROR);
            $return = $this->generateResponseArray(
                'An error occurred while processing your request. Contact the system administrator.',
                500
            );
            $this->sendJsonResponse($return['message'], $return['httpResponseCode']);
        }
        return false;
    }

    /**
     * @param int $userId
     * @return bool
     */
    public function delete(int $userId): bool
    {
        try {
            $sqlQuery = "DELETE FROM users WHERE id=:id;";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':id', $userId, PDO::PARAM_INT);
            return $statement->execute();
        } catch (\PDOException $exception) {
            $message = 'PDO error on method WjCrypto\Models\Database\UserDatabase\delete: ' . $exception->getMessage();
            $this->registerLog($message, 'database', 'UserDatabase', Logger::ERROR);
            $return = $this->generateResponseArray(
                'An error occurred while processing your request. Contact the system administrator.',
                500
            );
            $this->sendJsonResponse($return['message'], $return['httpResponseCode']);
        }
        return false;
    }

    /**
     * @param string $email
     * @param string $password
     * @param int $userId
     * @return bool
     */
    public function update(string $email, string $password, int $userId): bool
    {
        $encryptedEmail = $this->encrypt($email);
        $encryptedPassword = $this->encrypt($password);
        try {
            $sqlQuery = "UPDATE users SET email=:email, password=:password WHERE id=:id;";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':email', $encryptedEmail);
            $statement->bindParam(':password', $encryptedPassword);
            $statement->bindParam(':id', $userId, PDO::PARAM_INT);
            return $statement->execute();
        } catch (\PDOException $exception) {
            $message = 'PDO error on method WjCrypto\Models\Database\UserDatabase\update: ' . $exception->getMessage();
            $this->registerLog($message, 'database', 'UserDatabase', Logger::ERROR);
            $return = $this->generateResponseArray(
                'An error occurred while processing your request. Contact the system administrator.',
                500
            );
            $this->sendJsonResponse($return['message'], $return['httpResponseCode']);
        }
        return false;
    }

    /**
     * @param array $associativeArray
     * @return array
     */
    private function decryptArray(array $associativeArray): array
    {
        $associativeArray['email'] = $this->decrypt($associativeArray['email']);
        if (array_key_exists('password', $associativeArray) === true) {
            $associativeArray['password'] = $this->decrypt($associativeArray['password']);
        }
        return $associativeArray;
    }

    /**
     * @param array $associativeArray
     * @return User
     */
    private function createUserObject(array $associativeArray): User
    {
        $user = new User();
        $user->setId($associativeArray['id']);
        $user->setEmail($associativeArray['email']);
        if (array_key_exists('password', $associativeArray) === true) {
            $user->setPassword($associativeArray['password']);
        }
        $user->setCreationTimestamp($associativeArray['creation_timestamp']);
        $user->setUpdateTimestamp($associativeArray['update_timestamp']);
        return $user;
    }

}