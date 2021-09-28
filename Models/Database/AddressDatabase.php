<?php

namespace WjCrypto\Models\Database;

use Monolog\Logger;
use PDO;
use PDOException;
use WjCrypto\Helpers\CryptografyHelper;
use WjCrypto\Helpers\JsonResponse;
use WjCrypto\Helpers\LogHelper;
use WjCrypto\Models\Entities\Address;

class AddressDatabase extends Database
{
    use CryptografyHelper;
    use LogHelper;
    use JsonResponse;

    private PDO $connection;

    public function __construct()
    {
        $this->connection = static::getConnection();
    }

    /**
     * @param string $address
     * @param string $addressComplement
     * @param int $cityId
     * @return bool
     */
    public function insert(string $address, string $addressComplement, int $cityId): bool
    {
        $encryptedAddress = $this->encrypt($address);
        $encryptedAddressComplement = $this->encrypt($addressComplement);
        try {
            $sqlQuery = "INSERT INTO addresses (`address`, `complement`, `city_id`)" .
                "VALUES (:address, :complement, :city_id);";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':address', $encryptedAddress);
            $statement->bindParam(':complement', $encryptedAddressComplement);
            $statement->bindParam(':city_id', $cityId, PDO::PARAM_INT);
            $statement->execute();
            return true;
        } catch (PDOException $exception) {
            $message = 'PDO error on method WjCrypto\Models\Database\AddressDatabase\insert: ' .
                $exception->getMessage();
            $this->registerLog($message, 'database', 'AddressDatabase', Logger::ERROR);
        }
        return false;
    }

    /**
     * @param string $address
     * @return bool|Address
     */
    public function selectByAddress(string $address): bool|Address
    {
        $encryptedAddress = $this->encrypt($address);
        try {
            $sqlQuery = "SELECT * FROM addresses WHERE address=:address;";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':address', $encryptedAddress);
            $statement->execute();
            $statement->setFetchMode(PDO::FETCH_ASSOC);
            $row = $statement->fetch();
            if ($row === false) {
                return false;
            }
            $decryptedRow = $this->decryptRow($row);
            return $this->createAddressObject($decryptedRow);
        } catch (PDOException $exception) {
            $message = 'PDO error on method WjCrypto\Models\Database\AddressDatabase\selectByAddress: ' .
                $exception->getMessage();
            $this->registerLog($message, 'database', 'AddressDatabase', Logger::ERROR);
        }
        return false;
    }

    /**
     * @param int $id
     * @return bool|Address
     */
    public function selectById(int $id): bool|Address
    {
        try {
            $sqlQuery = "SELECT * FROM addresses WHERE id=:id;";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':id', $id, PDO::PARAM_INT);
            $statement->execute();
            $statement->setFetchMode(PDO::FETCH_ASSOC);
            $row = $statement->fetch();
            if ($row === false) {
                return false;
            }
            $decryptedRow = $this->decryptRow($row);
            return $this->createAddressObject($decryptedRow);
        } catch (PDOException $exception) {
            $message = 'PDO error on method WjCrypto\Models\Database\AddressDatabase\selectById: ' .
                $exception->getMessage();
            $this->registerLog($message, 'database', 'AddressDatabase', Logger::ERROR);
            $this->sendJsonMessage(
                'An error occurred while processing your request. Contact the system administrator.',
                500
            );
        }
        return false;
    }

    /**
     * @param int $id
     */
    public function delete(int $id): void
    {
        try {
            $sqlQuery = "DELETE FROM addresses WHERE id=:id;";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':id', $id, PDO::PARAM_INT);
            $statement->execute();
        } catch (PDOException $exception) {
            $message = 'PDO error on method WjCrypto\Models\Database\AddressDatabase\delete: ' .
                $exception->getMessage();
            $this->registerLog($message, 'database', 'AddressDatabase', Logger::ERROR);
            $this->sendJsonMessage(
                'An error occurred while processing your request. Contact the system administrator.',
                500
            );
        }
    }

    /**
     * @param array $associativeArray
     * @return array
     */
    private function decryptRow(array $associativeArray): array
    {
        $associativeArray['address'] = $this->decrypt($associativeArray['address']);
        $associativeArray['complement'] = $this->decrypt($associativeArray['complement']);
        return $associativeArray;
    }

    /**
     * @param array $associativeArray
     * @return Address
     */
    private function createAddressObject(array $associativeArray): Address
    {
        $accountNumber = new Address();
        $accountNumber->setId($associativeArray['id']);
        $accountNumber->setAddress($associativeArray['address']);
        $accountNumber->setComplement($associativeArray['complement']);
        $accountNumber->setCityId($associativeArray['city_id']);
        $accountNumber->setCreationTimestamp($associativeArray['creation_timestamp']);
        $accountNumber->setUpdateTimestamp($associativeArray['update_timestamp']);
        return $accountNumber;
    }
}
