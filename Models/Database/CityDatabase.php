<?php

namespace WjCrypto\Models\Database;

use PDO;
use WjCrypto\Models\Entities\City;

class CityDatabase extends Database
{
    private PDO $connection;

    public function __construct()
    {
        $this->connection = static::getConnection();
    }

    public function insert(string $name, int $stateId)
    {
        try {
            $sqlQuery = "INSERT INTO cities (`name`, `state_id`) VALUES (:name, :state_id);";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':name', $name);
            $statement->bindParam(':state_id', $stateId, PDO::PARAM_INT);
            if ($statement->execute()) {
                return true;
            }
            $errorArray = $statement->errorInfo();
            return $errorArray[2] . ' SQLSTATE error code: ' . $errorArray[0] . ' Driver error code: ' . $errorArray[1];
        } catch (\PDOException $exception) {
            return 'PDO error on method WjCrypto\Models\Database\CityDatabase\insert: ' . $exception->getMessage();
        }
    }

    /**
     * @return City[]|string
     */
    public function selectAllByState(int $stateId)
    {
        /**
         * @var $city City
         */
        try {
            $resultArray = [];
            $sqlQuery = "SELECT * FROM cities WHERE state_id=:state_id;";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':state_id', $stateId, PDO::PARAM_INT);
            if ($statement->execute()) {
                $statement->setFetchMode(PDO::FETCH_CLASS, City::class);
                $queryReturn = $statement->fetchAll();
                foreach ($queryReturn as $city) {
                    $resultArray[] = $city;
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
     * @return City|string
     */
    public function selectById(int $id)
    {
        /**
         * @var $city City
         */
        try {
            $sqlQuery = "SELECT * FROM cities WHERE id=:id;";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':id', $id, PDO::PARAM_INT);
            if ($statement->execute()) {
                $statement->setFetchMode(PDO::FETCH_CLASS, City::class);
                return $statement->fetch();
            }
            $errorArray = $statement->errorInfo();
            return $errorArray[2] . ' SQLSTATE error code: ' . $errorArray[0] . ' Driver error code: ' . $errorArray[1];
        } catch (\PDOException $exception) {
            return 'PDO error on method WjCrypto\Models\Database\UserDatabase\selectAll: ' . $exception->getMessage();
        }
    }
}