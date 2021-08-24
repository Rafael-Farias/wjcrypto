<?php

namespace WjCrypto\Models\Database;

use PDO;
use WjCrypto\Models\Entities\User;

class UserDatabase extends Database
{
    private $connection = null;

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
        try {
            $sqlQuery = "INSERT INTO users (`email`, `password`) VALUES (:email, :password);";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':email', $email);
            $statement->bindParam(':password', $password);
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
        /**
         * @var $user User
         */
        try {
            $resultArray = [];
            $sqlQuery = "SELECT `id`,`email`,`creation_timestamp`,`update_timestamp` FROM users;";
            $statement = $this->connection->prepare($sqlQuery);
            if ($statement->execute()) {
                $statement->setFetchMode(PDO::FETCH_CLASS, User::class);
                $queryReturn = $statement->fetchAll();
                foreach ($queryReturn as $user) {
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
            if ($statement->execute()) {
                $statement->setFetchMode(PDO::FETCH_CLASS, User::class);
                return $statement->fetch();
            }
            $errorArray = $statement->errorInfo();
            return $errorArray[2] . ' SQLSTATE error code: ' . $errorArray[0] . ' Driver error code: ' . $errorArray[1];
        } catch (\PDOException $exception) {
            return 'PDO error on method WjCrypto\Models\Database\UserDatabase\selectAll: ' . $exception->getMessage();
        }
    }

}