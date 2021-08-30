<?php

namespace WjCrypto\Models\Database;

use PDO;
use WjCrypto\Models\Entities\Address;

class AddressDatabase extends Database
{
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
    public function insert(string $address, string $addressComplement, int $cityId)
    {
        try {
            $sqlQuery = "INSERT INTO addresses (`address`, `complement`, `city_id`) VALUES (:address, :complement, :city_id);";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':address', $address);
            $statement->bindParam(':complement', $addressComplement);
            $statement->bindParam(':city_id', $cityId, PDO::PARAM_INT);
            if ($statement->execute()) {
                return true;
            }
            $errorArray = $statement->errorInfo();
            return $errorArray[2] . ' SQLSTATE error code: ' . $errorArray[0] . ' Driver error code: ' . $errorArray[1];
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
        try {
            $sqlQuery = "SELECT * FROM addresses WHERE address=:address;";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':address', $address);
            if ($statement->execute()) {
                $statement->setFetchMode(PDO::FETCH_CLASS, Address::class);
                return $statement->fetch();
            }
            $errorArray = $statement->errorInfo();
            return $errorArray[2] . ' SQLSTATE error code: ' . $errorArray[0] . ' Driver error code: ' . $errorArray[1];
        } catch (\PDOException $exception) {
            return 'PDO error on method WjCrypto\Models\Database\UserDatabase\selectById: ' . $exception->getMessage();
        }
    }
}