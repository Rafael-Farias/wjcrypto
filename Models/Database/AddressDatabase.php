<?php

namespace WjCrypto\Models\Database;

use PDO;
use WjCrypto\Helpers\CryptografyHelper;
use WjCrypto\Models\Entities\Address;

class AddressDatabase extends Database
{
    use CryptografyHelper;

    private PDO $connection;

    public function __construct()
    {
        $this->connection = static::getConnection();
    }

    /**
     * @param string $address
     * @param string $addressComplement
     * @param int $cityId
     * @return bool|string
     */
    public function insert(string $address, string $addressComplement, int $cityId): bool|string
    {
        $encryptedAddress = $this->encrypt($address);
        $encryptedAddressComplement = $this->encrypt($addressComplement);
        try {
            $sqlQuery = "INSERT INTO addresses (`address`, `complement`, `city_id`) VALUES (:address, :complement, :city_id);";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':address', $encryptedAddress);
            $statement->bindParam(':complement', $encryptedAddressComplement);
            $statement->bindParam(':city_id', $cityId, PDO::PARAM_INT);
            return $statement->execute();
        } catch (\PDOException $exception) {
            return 'PDO error on method WjCrypto\Models\Database\AddressDatabase\insert: ' . $exception->getMessage();
        }
    }

    /**
     * @param string $address
     * @return Address|string
     */
    public function selectByAddress(string $address)
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
                return $row;
            }
            $decryptedRow = $this->decryptRow($row);
            return $this->createAddressObject($decryptedRow);
        } catch (\PDOException $exception) {
            return 'PDO error on method WjCrypto\Models\Database\UserDatabase\selectByAddress: ' . $exception->getMessage(
                );
        }
    }

    /**
     * @param int $id
     * @return Address|string
     */
    public function selectById(int $id)
    {
        try {
            $sqlQuery = "SELECT * FROM addresses WHERE id=:id;";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':id', $id, PDO::PARAM_INT);
            $statement->execute();
            $statement->setFetchMode(PDO::FETCH_ASSOC);
            $row = $statement->fetch();
            if ($row === false) {
                return $row;
            }
            $decryptedRow = $this->decryptRow($row);
            return $this->createAddressObject($decryptedRow);
        } catch (\PDOException $exception) {
            return 'PDO error on method WjCrypto\Models\Database\UserDatabase\selectById: ' . $exception->getMessage();
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