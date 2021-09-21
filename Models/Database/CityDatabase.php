<?php

namespace WjCrypto\Models\Database;

use PDO;
use WjCrypto\Helpers\CryptografyHelper;
use WjCrypto\Models\Entities\City;

class CityDatabase extends Database
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
            $sqlQuery = "INSERT INTO cities (`name`, `state_id`) VALUES (:name, (SELECT `id` FROM states WHERE initials=:state_initials));";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':name', $encryptedName);
            $statement->bindParam(':state_initials', $encryptedStateInitials);
            return $statement->execute();
        } catch (\PDOException $exception) {
            return 'PDO error on method WjCrypto\Models\Database\CityDatabase\insert: ' . $exception->getMessage();
        }
    }

    /**
     * @return City[]|string
     */
    public function selectAllByState(int $stateId): string|array
    {
        try {
            $resultArray = [];
            $sqlQuery = "SELECT * FROM cities WHERE state_id=:state_id;";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':state_id', $stateId, PDO::PARAM_INT);
            $statement->execute();
            $statement->setFetchMode(PDO::FETCH_ASSOC);
            $queryReturn = $statement->fetchAll();
            if (empty($queryReturn)) {
                return false;
            }
            foreach ($queryReturn as $row) {
                $decryptedRow = $this->decryptRow($row);
                $city = $this->createCityObject($decryptedRow);
                $resultArray[] = $city;
            }
            return $resultArray;
        } catch (\PDOException $exception) {
            return 'PDO error on method WjCrypto\Models\Database\CityDatabase\selectAllByState: ' . $exception->getMessage(
                );
        }
    }

    /**
     * @param int $id
     * @return City|string
     */
    public function selectById(int $id): string|City
    {
        try {
            $sqlQuery = "SELECT * FROM cities WHERE id=:id;";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':id', $id, PDO::PARAM_INT);
            $statement->execute();
            $statement->setFetchMode(PDO::FETCH_ASSOC);
            $row = $statement->fetch();
            if ($row === false) {
                return $row;
            }
            $decryptedRow = $this->decryptRow($row);
            return $this->createCityObject($decryptedRow);
        } catch (\PDOException $exception) {
            return 'PDO error on method WjCrypto\Models\Database\CityDatabase\selectById: ' . $exception->getMessage();
        }
    }

    /**
     * @param array $associativeArray
     * @return array
     */
    private function decryptRow(array $associativeArray): array
    {
        $associativeArray['name'] = $this->decrypt($associativeArray['name']);
        return $associativeArray;
    }

    /**
     * @param array $associativeArray
     * @return City
     */
    private function createCityObject(array $associativeArray): City
    {
        $city = new City();
        $city->setId($associativeArray['id']);
        $city->setName($associativeArray['name']);
        $city->setStateId($associativeArray['state_id']);
        $city->setCreationTimestamp($associativeArray['creation_timestamp']);
        $city->setUpdateTimestamp($associativeArray['update_timestamp']);
        return $city;
    }
}