<?php

namespace WjCrypto\Models\Database;

use PDO;
use PDOException;

class LegalPersonAccountDatabase extends Database
{
    private PDO $connection;

    public function __construct()
    {
        $this->connection = static::getConnection();
    }

    public function insert(
        string $name,
        string $cnpj,
        string $companyRegister,
        string $foundationDate,
        string $balance,
        int $addressId
    ) {
        try {
            $sqlQuery = "INSERT INTO legal_person_accounts (`name`, `cnpj`, `company_register`, `foundation_date`, `balance`, `address_id`) VALUES (:name, :cnpj, :company_register, :foundation_date, :balance, :address_id);";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':name', $name);
            $statement->bindParam(':cnpj', $cnpj);
            $statement->bindParam(':company_register', $companyRegister);
            $statement->bindParam(':foundation_date', $foundationDate);
            $statement->bindParam(':balance', $balance);
            $statement->bindParam(':address_id', $addressId);
            if ($statement->execute()) {
                return true;
            }
            $errorArray = $statement->errorInfo();
            return $errorArray[2] . ' SQLSTATE error code: ' . $errorArray[0] . ' Driver error code: ' . $errorArray[1];
        } catch (PDOException $exception) {
            return 'PDO error on method WjCrypto\Models\Database\LegalPersonAccountDatabase\insert: ' . $exception->getMessage(
                );
        }
    }

    /**
     * @param string $cnpj
     * @return \stdClass|string
     */
    public function selectByCnpj(string $cnpj)
    {
        try {
            $sqlQuery = "SELECT * FROM legal_person_accounts WHERE cnpj=:cnpj;";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':cnpj', $cnpj);
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
     * @param int $id
     * @return \stdClass|string
     */
    public function selectById(int $id)
    {
        try {
            $sqlQuery = "SELECT * FROM legal_person_accounts WHERE id=:id;";
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

    public function updateAccountBalance(string $balance, int $id)
    {
        try {
            $sqlQuery = "UPDATE legal_person_accounts SET balance=:balance WHERE id=:id;";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':balance', $balance);
            $statement->bindParam(':id', $id, PDO::PARAM_INT);
            return $statement->execute();
        } catch (\PDOException $exception) {
            return 'PDO error on method WjCrypto\Models\Database\UserDatabase\selectById: ' . $exception->getMessage();
        }
    }
}