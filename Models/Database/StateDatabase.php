<?php

namespace WjCrypto\Models\Database;

use PDO;
use WjCrypto\Helpers\CryptografyHelper;
use WjCrypto\Models\Entities\State;

class StateDatabase extends Database
{
    use CryptografyHelper;

    private PDO $connection;

    public function __construct()
    {
        $this->connection = static::getConnection();
    }

    /**
     * @param string $name
     * @param string $stateInitials
     * @return bool|string
     */
    public function insert(string $name, string $stateInitials): bool|string
    {
        $encryptedName = $this->encrypt($name);
        $encryptedStateInitials = $this->encrypt($stateInitials);
        try {
            $sqlQuery = "INSERT INTO states (`name`, `initials`) VALUES (:name,:state_initials);";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':name', $encryptedName);
            $statement->bindParam(':state_initials', $encryptedStateInitials);
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
     * @return State[]|string
     */
    public function selectAll(): array|string
    {
        try {
            $resultArray = [];
            $sqlQuery = "SELECT * FROM states;";
            $statement = $this->connection->prepare($sqlQuery);
            if ($statement->execute()) {
                $statement->setFetchMode(PDO::FETCH_ASSOC);
                $queryReturn = $statement->fetchAll();
                foreach ($queryReturn as $row) {
                    $decryptedRow = $this->decryptRow($row);
                    $state = $this->createStateObject($decryptedRow);
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
     * @param int $id
     * @return State|string
     */
    public function selectById(int $id): State|string
    {
        try {
            $sqlQuery = "SELECT * FROM states where id=:id;";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':id', $id, PDO::PARAM_INT);
            if ($statement->execute()) {
                $statement->setFetchMode(PDO::FETCH_ASSOC);
                $row = $statement->fetch();
                if ($row === false) {
                    throw new \PDOException('Could not find the city in the database.');
                }
                $decryptedRow = $this->decryptRow($row);
                return $this->createStateObject($decryptedRow);
            }
            $errorArray = $statement->errorInfo();
            return $errorArray[2] . ' SQLSTATE error code: ' . $errorArray[0] . ' Driver error code: ' . $errorArray[1];
        } catch (\PDOException $exception) {
            return 'PDO error on method WjCrypto\Models\Database\UserDatabase\selectAll: ' . $exception->getMessage();
        }
    }

    /**
     * @param array $associativeArray
     * @return array
     */
    private function decryptRow(array $associativeArray): array
    {
        $associativeArray['name'] = $this->decrypt($associativeArray['name']);
        $associativeArray['initials'] = $this->decrypt($associativeArray['initials']);
        return $associativeArray;
    }

    /**
     * @param array $associativeArray
     * @return State
     */
    private function createStateObject(array $associativeArray): State
    {
        $city = new State();
        $city->setId($associativeArray['id']);
        $city->setName($associativeArray['name']);
        $city->setInitials($associativeArray['initials']);
        $city->setCreationTimestamp($associativeArray['creation_timestamp']);
        $city->setUpdateTimestamp($associativeArray['update_timestamp']);
        return $city;
    }
}