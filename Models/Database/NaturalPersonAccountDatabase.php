<?php

namespace WjCrypto\Models\Database;

use PDO;
use WjCrypto\Models\Entities\NaturalPersonAccount;

class NaturalPersonAccountDatabase extends Database
{
    private PDO $connection;

    public function __construct()
    {
        $this->connection = static::getConnection();
    }

    public function insert(string $name, string $cpf, string $rg, string $birthDate, float $balance, int $addressId)
    {
        try {
            $sqlQuery = "INSERT INTO natural_person_accounts (`name`, `cpf`, `rg`, `birth_date`, `balance`, `address_id`) VALUES (:name, :cpf, :rg, :birth_date, :balance, :address_id);";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':name', $name);
            $statement->bindParam(':cpf', $cpf);
            $statement->bindParam(':rg', $rg);
            $statement->bindParam(':birth_date', $birthDate);
            $statement->bindParam(':balance', $balance);
            $statement->bindParam(':address_id', $addressId);
            if ($statement->execute()) {
                return true;
            }
            $errorArray = $statement->errorInfo();
            return $errorArray[2] . ' SQLSTATE error code: ' . $errorArray[0] . ' Driver error code: ' . $errorArray[1];
        } catch (\PDOException $exception) {
            return 'PDO error on method WjCrypto\Models\Database\NaturalPersonAccountDatabase\insert: ' . $exception->getMessage(
                );
        }
    }

    /**
     * @param int $id
     * @return \stdClass|string
     */
    public function selectById(int $id)
    {
        try {
            $sqlQuery = "SELECT * FROM natural_person_accounts WHERE id=:id;";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':id', $id);
            if ($statement->execute()) {
                $statement->setFetchMode(PDO::FETCH_OBJ);
                return $statement->fetch();
            }
            $errorArray = $statement->errorInfo();
            return $errorArray[2] . ' SQLSTATE error code: ' . $errorArray[0] . ' Driver error code: ' . $errorArray[1];
        } catch (\PDOException $exception) {
            return 'PDO error on method WjCrypto\Models\Database\UserDatabase\selectById: ' . $exception->getMessage();
        }
    }

    /**
     * @param string $cpf
     * @return \stdClass|string
     */
    public function selectByCpf(string $cpf)
    {
        try {
            $sqlQuery = "SELECT * FROM natural_person_accounts WHERE cpf=:cpf;";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':cpf', $cpf);
            if ($statement->execute()) {
                $statement->setFetchMode(PDO::FETCH_OBJ);
                return $statement->fetch();
            }
            $errorArray = $statement->errorInfo();
            return $errorArray[2] . ' SQLSTATE error code: ' . $errorArray[0] . ' Driver error code: ' . $errorArray[1];
        } catch (\PDOException $exception) {
            return 'PDO error on method WjCrypto\Models\Database\UserDatabase\selectById: ' . $exception->getMessage();
        }
    }
}