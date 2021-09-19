<?php

namespace WjCrypto\Models\Database;

use PDO;
use WjCrypto\Helpers\CryptografyHelper;
use WjCrypto\Models\Entities\AccountNumber;

class AccountNumberDatabase extends Database
{
    use CryptografyHelper;

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
        $encryptedAccountNumber = $this->encrypt($accountNumber);
        try {
            $sqlQuery = "INSERT INTO accounts_number (`user_id`, `account_number`, `legal_person_account_id`, `natural_person_account_id`) VALUES (:user_id, :account_number, :legal_person_account_id, :natural_person_account_id);";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':user_id', $userId);
            $statement->bindParam(':account_number', $encryptedAccountNumber);
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
    public function selectAll(): string|array
    {
        /**
         * @var $accountNumber AccountNumber
         */
        try {
            $resultArray = [];
            $sqlQuery = "SELECT * FROM accounts_number;";
            $statement = $this->connection->prepare($sqlQuery);
            if ($statement->execute()) {
                $statement->setFetchMode(PDO::FETCH_ASSOC);
                $queryReturn = $statement->fetchAll();
                foreach ($queryReturn as $row) {
                    $decryptedRow = $this->decryptRow($row);
                    $resultArray[] = $this->createAccountNumberObject($decryptedRow);
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
     * @return AccountNumber|string|false
     */
    public function selectByAccountNumber(int $accountNumber): AccountNumber|bool|string
    {
        $encryptedAccountNumber = $this->encrypt($accountNumber);
        try {
            $sqlQuery = "SELECT * FROM accounts_number where account_number=:account_number;";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':account_number', $encryptedAccountNumber);
            $statement->execute();
            $statement->setFetchMode(PDO::FETCH_ASSOC);
            $row = $statement->fetch();
            if ($row === false) {
                throw new \PDOException('Could not find the account number in the database.');
            }
            $decryptedRow = $this->decryptRow($row);
            return $this->createAccountNumberObject($decryptedRow);
        } catch (\PDOException $exception) {
            return 'PDO error on method WjCrypto\Models\Database\UserDatabase\selectAll: ' . $exception->getMessage();
        }
    }

    /**
     * @param int $userId
     * @return AccountNumber|string
     */
    public function selectByUserId(int $userId)
    {
        try {
            $sqlQuery = "SELECT * FROM accounts_number where user_id=:user_id;";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $statement->execute();
            $statement->setFetchMode(PDO::FETCH_ASSOC);
            $row = $statement->fetch();
            $decryptedRow = $this->decryptRow($row);
            return $this->createAccountNumberObject($decryptedRow);
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
        $associativeArray['account_number'] = $this->decrypt($associativeArray['account_number']);
        return $associativeArray;
    }

    /**
     * @param array $associativeArray
     * @return AccountNumber
     */
    private function createAccountNumberObject(array $associativeArray): AccountNumber
    {
        $accountNumber = new AccountNumber();
        $accountNumber->setId($associativeArray['id']);
        $accountNumber->setUserId($associativeArray['user_id']);
        $accountNumber->setAccountNumber($associativeArray['account_number']);
        $accountNumber->setLegalPersonAccountId($associativeArray['legal_person_account_id']);
        $accountNumber->setNaturalPersonAccountId($associativeArray['natural_person_account_id']);
        $accountNumber->setCreationTimestamp($associativeArray['creation_timestamp']);
        $accountNumber->setUpdateTimestamp($associativeArray['update_timestamp']);
        return $accountNumber;
    }
}