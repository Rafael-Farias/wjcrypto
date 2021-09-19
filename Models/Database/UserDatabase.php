<?php

namespace WjCrypto\Models\Database;

use PDO;
use WjCrypto\Helpers\CryptografyHelper;
use WjCrypto\Models\Entities\User;

class UserDatabase extends Database
{
    use CryptografyHelper;

    private PDO $connection;

    public function __construct()
    {
        $this->connection = static::getConnection();
    }

    /**
     * @param string $email
     * @param string $password
     * @return bool|string
     */
    public function insert(string $email, string $password)
    {
        $encryptedEmail = $this->encrypt($email);
        $encryptedPassword = $this->encrypt($password);
        try {
            $sqlQuery = "INSERT INTO users (`email`, `password`) VALUES (:email, :password);";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':email', $encryptedEmail);
            $statement->bindParam(':password', $encryptedPassword);
            if ($statement->execute()) {
                return true;
            }
            $errorArray = $statement->errorInfo();
            return $errorArray[2] . ' SQLSTATE error code: ' . $errorArray[0] . ' Driver error code: ' . $errorArray[1];
        } catch (\PDOException $exception) {
            return 'PDO error on method WjCrypto\Models\Database\UserDatabase\insert: ' . $exception->getMessage();
        }
    }

    /**
     * @return User[]|string
     */
    public function selectAll()
    {
        try {
            $resultArray = [];
            $sqlQuery = "SELECT * FROM users;";
            $statement = $this->connection->prepare($sqlQuery);
            if ($statement->execute()) {
                $statement->setFetchMode(PDO::FETCH_ASSOC);
                $queryReturn = $statement->fetchAll();
                foreach ($queryReturn as $userAssociativeArray) {
                    $decryptedArray = $this->decryptArray($userAssociativeArray);
                    $user = $this->createUserObject($decryptedArray);
                    $resultArray[] = $user;
                }
                return $resultArray;
            }
            $errorArray = $statement->errorInfo();
            return $errorArray[2] . ' SQLSTATE error code: ' . $errorArray[0] . ' Driver error code: ' . $errorArray[1];
        } catch (\PDOException $exception) {
            return 'PDO error on method WjCrypto\Models\Database\UserDatabase\selectAll: ' . $exception->getMessage();
        }
    }

    /**
     * @param int $userId
     * @return User|string|bool
     */
    public function selectById(int $userId)
    {
        try {
            $sqlQuery = "SELECT `id`,`email`,`creation_timestamp`,`update_timestamp` FROM users WHERE id=:id;";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':id', $userId, PDO::PARAM_INT);
            if ($statement->execute()) {
                $statement->setFetchMode(PDO::FETCH_ASSOC);
                $userAssociativeArray = $statement->fetch();
                $decryptedArray = $this->decryptArray($userAssociativeArray);
                return $this->createUserObject($decryptedArray);
            }
            $errorArray = $statement->errorInfo();
            return $errorArray[2] . ' SQLSTATE error code: ' . $errorArray[0] . ' Driver error code: ' . $errorArray[1];
        } catch (\PDOException $exception) {
            return 'PDO error on method WjCrypto\Models\Database\UserDatabase\selectById: ' . $exception->getMessage();
        }
    }

    /**
     * @param int $userId
     * @return bool|string
     */
    public function delete(int $userId)
    {
        try {
            $sqlQuery = "DELETE FROM users WHERE id=:id;";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':id', $userId, PDO::PARAM_INT);
            if ($statement->execute()) {
                return true;
            }
            $errorArray = $statement->errorInfo();
            return $errorArray[2] . ' SQLSTATE error code: ' . $errorArray[0] . ' Driver error code: ' . $errorArray[1];
        } catch (\PDOException $exception) {
            return 'PDO error on method WjCrypto\Models\Database\UserDatabase\delete: ' . $exception->getMessage();
        }
    }

    public function update(string $email, string $password, int $userId)
    {
        $encryptedEmail = $this->encrypt($email);
        $encryptedPassword = $this->encrypt($password);
        try {
            $sqlQuery = "UPDATE users SET email=:email, password=:password WHERE id=:id;";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':email', $encryptedEmail);
            $statement->bindParam(':password', $encryptedPassword);
            $statement->bindParam(':id', $userId, PDO::PARAM_INT);
            if ($statement->execute()) {
                return true;
            }
            $errorArray = $statement->errorInfo();
            return $errorArray[2] . ' SQLSTATE error code: ' . $errorArray[0] . ' Driver error code: ' . $errorArray[1];
        } catch (\PDOException $exception) {
            return 'PDO error on method WjCrypto\Models\Database\UserDatabase\update: ' . $exception->getMessage();
        }
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