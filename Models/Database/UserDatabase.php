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
    public function insert(string $email, string $password): bool|string
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
            return 'PDO error on method WjCrypto\Models\Database\UserDatabase\insert: ' . $exception->getMessage();
        }
    }

    /**
     * @return User[]|string|bool
     */
    public function selectAll(): array|string|bool
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
            return 'PDO error on method WjCrypto\Models\Database\UserDatabase\selectAll: ' . $exception->getMessage();
        }
    }

    /**
     * @param int $userId
     * @return User|string|bool
     */
    public function selectById(int $userId): User|bool|string
    {
        try {
            $sqlQuery = "SELECT `id`,`email`,`creation_timestamp`,`update_timestamp` FROM users WHERE id=:id;";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':id', $userId, PDO::PARAM_INT);
            $statement->execute();
            $statement->setFetchMode(PDO::FETCH_ASSOC);
            $row = $statement->fetch();
            if ($row === false) {
                return $row;
            }
            $decryptedArray = $this->decryptArray($row);
            return $this->createUserObject($decryptedArray);
        } catch (\PDOException $exception) {
            return 'PDO error on method WjCrypto\Models\Database\UserDatabase\selectById: ' . $exception->getMessage();
        }
    }

    /**
     * @param int $userId
     * @return bool|string
     */
    public function delete(int $userId): bool|string
    {
        try {
            $sqlQuery = "DELETE FROM users WHERE id=:id;";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':id', $userId, PDO::PARAM_INT);
            return $statement->execute();
        } catch (\PDOException $exception) {
            return 'PDO error on method WjCrypto\Models\Database\UserDatabase\delete: ' . $exception->getMessage();
        }
    }

    /**
     * @param string $email
     * @param string $password
     * @param int $userId
     * @return bool|string
     */
    public function update(string $email, string $password, int $userId): bool|string
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