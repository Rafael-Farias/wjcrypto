<?php

namespace WjCrypto\Models\Database;

use PDO;
use PDOException;
use WjCrypto\Models\Entities\LegalPersonAccount;

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
        float $balance,
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
     * @return LegalPersonAccount|string
     */
    public function selectByCnpj(string $cnpj)
    {
        try {
            $sqlQuery = "SELECT * FROM legal_person_accounts WHERE cnpj=:cnpj;";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':cnpj', $cnpj);
            if ($statement->execute()) {
                $statement->setFetchMode(PDO::FETCH_CLASS, LegalPersonAccount::class);
                return $statement->fetch();
            }
            $errorArray = $statement->errorInfo();
            return $errorArray[2] . ' SQLSTATE error code: ' . $errorArray[0] . ' Driver error code: ' . $errorArray[1];
        } catch (\PDOException $exception) {
            return 'PDO error on method WjCrypto\Models\Database\UserDatabase\selectById: ' . $exception->getMessage();
        }
    }
}