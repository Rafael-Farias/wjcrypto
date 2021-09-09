<?php

namespace WjCrypto\Models\Database;

use PDO;
use WjCrypto\Models\Entities\AccountNumber;

class AccountNumberDatabase extends Database
{
    private PDO $connection;

    public function __construct()
    {
        $this->connection = static::getConnection();
    }

    public function insert(
        int $userId,
        int $accountNumber,
        int $legalPersonAccountId = null,
        int $naturalPersonAccountId = null
    ) {
        try {
            $sqlQuery = "INSERT INTO accounts_number (`user_id`, `account_number`, `legal_person_account_id`, `natural_person_account_id`) VALUES (:user_id, :account_number, :legal_person_account_id, :natural_person_account_id);";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':user_id', $userId);
            $statement->bindParam(':account_number', $accountNumber, PDO::PARAM_INT);
            $statement->bindParam(':legal_person_account_id', $legalPersonAccountId, PDO::PARAM_INT);
            $statement->bindParam(':natural_person_account_id', $naturalPersonAccountId, PDO::PARAM_INT);
            if ($statement->execute()) {
                return true;
            }
            $errorArray = $statement->errorInfo();
            return $errorArray[2] . ' SQLSTATE error code: ' . $errorArray[0] . ' Driver error code: ' . $errorArray[1];
        } catch (\PDOException $exception) {
            return 'PDO error on method WjCrypto\Models\Database\AccountNumberDatabase\insert: ' . $exception->getMessage(
                );
        }
    }

    /**
     * @return AccountNumber[]|string
     */
    public function selectAll()
    {
        /**
         * @var $accountNumber AccountNumber
         */
        try {
            $resultArray = [];
            $sqlQuery = "SELECT * FROM accounts_number;";
            $statement = $this->connection->prepare($sqlQuery);
            if ($statement->execute()) {
                $statement->setFetchMode(PDO::FETCH_CLASS, AccountNumber::class);
                $queryReturn = $statement->fetchAll();
                foreach ($queryReturn as $accountNumber) {
                    $resultArray[] = $accountNumber;
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
     * @param int $accountNumber
     * @return AccountNumber|string
     */
    public function selectByAccountNumber(int $accountNumber)
    {
        /**
         * @var $accountNumber AccountNumber
         */
        try {
            $sqlQuery = "SELECT * FROM accounts_number where account_number=:account_number;";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':account_number', $accountNumber, PDO::PARAM_INT);
            $statement->execute();
            $statement->setFetchMode(PDO::FETCH_CLASS, AccountNumber::class);
            return $statement->fetch();
        } catch (\PDOException $exception) {
            return 'PDO error on method WjCrypto\Models\Database\UserDatabase\selectAll: ' . $exception->getMessage();
        }
    }
}