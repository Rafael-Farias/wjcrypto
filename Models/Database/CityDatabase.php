<?php

namespace WjCrypto\Models\Database;

use Monolog\Logger;
use PDO;
use WjCrypto\Helpers\CryptografyHelper;
use WjCrypto\Helpers\JsonResponse;
use WjCrypto\Helpers\LogHelper;
use WjCrypto\Helpers\ResponseArray;
use WjCrypto\Models\Entities\City;

class CityDatabase extends Database
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
     * @param string $name
     * @param string $stateInitials
     * @return void
     */
    public function insert(string $name, string $stateInitials): void
    {
        $encryptedName = $this->encrypt($name);
        $encryptedStateInitials = $this->encrypt($stateInitials);
        try {
            $sqlQuery = "INSERT INTO cities (`name`, `state_id`) VALUES (:name, (SELECT `id` FROM states WHERE initials=:state_initials));";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':name', $encryptedName);
            $statement->bindParam(':state_initials', $encryptedStateInitials);
            $statement->execute();
        } catch (\PDOException $exception) {
            $message = 'PDO error on method WjCrypto\Models\Database\CityDatabase\insert: ' . $exception->getMessage();
            $this->registerLog($message, 'database', 'CityDatabase', Logger::ERROR);
            $return = $this->generateResponseArray(
                'An error occurred while processing your request. Contact the system administrator.',
                500
            );
            $this->sendJsonResponse($return['message'], $return['httpResponseCode']);
        }
    }

    /**
     * @param int $stateId
     * @return array|bool
     */
    public function selectAllByState(int $stateId): array|bool
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
            $message = 'PDO error on method WjCrypto\Models\Database\CityDatabase\selectAllByState: ' . $exception->getMessage(
                );
            $this->registerLog($message, 'database', 'CityDatabase', Logger::ERROR);
            $return = $this->generateResponseArray(
                'An error occurred while processing your request. Contact the system administrator.',
                500
            );
            $this->sendJsonResponse($return['message'], $return['httpResponseCode']);
        }
        return false;
    }

    /**
     * @param int $id
     * @return bool|City
     */
    public function selectById(int $id): bool|City
    {
        try {
            $sqlQuery = "SELECT * FROM cities WHERE id=:id;";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':id', $id, PDO::PARAM_INT);
            $statement->execute();
            $statement->setFetchMode(PDO::FETCH_ASSOC);
            $row = $statement->fetch();
            if ($row === false) {
                return false;
            }
            $decryptedRow = $this->decryptRow($row);
            return $this->createCityObject($decryptedRow);
        } catch (\PDOException $exception) {
            $message = 'PDO error on method WjCrypto\Models\Database\CityDatabase\selectById: ' . $exception->getMessage(
                );
            $this->registerLog($message, 'database', 'CityDatabase', Logger::ERROR);
            $this->sendJsonMessage(
                'An error occurred while processing your request. Contact the system administrator.',
                500
            );
        }
        return false;
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