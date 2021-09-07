<?php

namespace WjCrypto\Models\Database;

use PDO;
use WjCrypto\Models\Entities\State;

class StateDatabase extends Database
{
    private PDO $connection;

    public function __construct()
    {
        $this->connection = static::getConnection();
    }

    /**
     * @return State[]|string
     */
    public function selectAll()
    {
        /**
         * @var $state State
         */
        try {
            $resultArray = [];
            $sqlQuery = "SELECT * FROM states;";
            $statement = $this->connection->prepare($sqlQuery);
            if ($statement->execute()) {
                $statement->setFetchMode(PDO::FETCH_CLASS, State::class);
                $queryReturn = $statement->fetchAll();
                foreach ($queryReturn as $state) {
                    $resultArray[] = $state;
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
     * @return State|string
     */
    public function selectById(int $id)
    {
        /**
         * @var $state State
         */
        try {
            $sqlQuery = "SELECT * FROM states where id=:id;";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':id', $id, PDO::PARAM_INT);
            if ($statement->execute()) {
                $statement->setFetchMode(PDO::FETCH_CLASS, State::class);
                return $statement->fetch();
            }
            $errorArray = $statement->errorInfo();
            return $errorArray[2] . ' SQLSTATE error code: ' . $errorArray[0] . ' Driver error code: ' . $errorArray[1];
        } catch (\PDOException $exception) {
            return 'PDO error on method WjCrypto\Models\Database\UserDatabase\selectAll: ' . $exception->getMessage();
        }
    }
}