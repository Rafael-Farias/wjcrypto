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

    /**
     * @param int $userId
     * @param int $accountNumber
     * @param int|null $legalPersonAccountId
     * @param int|null $naturalPersonAccountId
     * @return bool|string
     */
    public function insert(
        int $userId,
        int $accountNumber,
        int $legalPersonAccountId = null,
        int $naturalPersonAccountId = null
    ): bool|string {
        $encryptedAccountNumber = $this->encrypt($accountNumber);
        try {
            $sqlQuery = "INSERT INTO accounts_number (`user_id`, `account_number`, `legal_person_account_id`, `natural_person_account_id`) VALUES (:user_id, :account_number, :legal_person_account_id, :natural_person_account_id);";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':user_id', $userId);
            $statement->bindParam(':account_number', $encryptedAccountNumber);
            $statement->bindParam(':legal_person_account_id', $legalPersonAccountId, PDO::PARAM_INT);
            $statement->bindParam(':natural_person_account_id', $naturalPersonAccountId, PDO::PARAM_INT);
            return $statement->execute();
        } catch (\PDOException $exception) {
            return 'PDO error on method WjCrypto\Models\Database\AccountNumberDatabase\insert: ' . $exception->getMessage(
                );
        }
    }

    /**
     * @return AccountNumber[]|string|bool
     */
    public function selectAll(): string|array|bool
    {
        try {
            $resultArray = [];
            $sqlQuery = "SELECT * FROM accounts_number;";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->execute();
            $statement->setFetchMode(PDO::FETCH_ASSOC);
            $queryReturn = $statement->fetchAll();
            if (empty($queryReturn)) {
                return false;
            }
            foreach ($queryReturn as $row) {
                $decryptedRow = $this->decryptRow($row);
                $accountNumber = $this->createAccountNumberObject($decryptedRow);
                $resultArray[] = $accountNumber;
            }
            return $resultArray;
        } catch (\PDOException $exception) {
            return 'PDO error on method WjCrypto\Models\Database\AccountNumberDatabase\selectAll: ' . $exception->getMessage(
                );
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
                return $row;
            }
            $decryptedRow = $this->decryptRow($row);
            return $this->createAccountNumberObject($decryptedRow);
        } catch (\PDOException $exception) {
            return 'PDO error on method WjCrypto\Models\Database\AccountNumberDatabase\selectByAccountNumber: ' . $exception->getMessage(
                );
        }
    }

    /**
     * @param int $userId
     * @return AccountNumber|string|bool
     */
    public function selectByUserId(int $userId): AccountNumber|bool|string
    {
        try {
            $sqlQuery = "SELECT * FROM accounts_number where user_id=:user_id;";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $statement->execute();
            $statement->setFetchMode(PDO::FETCH_ASSOC);
            $row = $statement->fetch();
            if ($row === false) {
                return $row;
            }
            $decryptedRow = $this->decryptRow($row);
            return $this->createAccountNumberObject($decryptedRow);
        } catch (\PDOException $exception) {
            return 'PDO error on method WjCrypto\Models\Database\AccountNumberDatabase\selectByUserId: ' . $exception->getMessage(
                );
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