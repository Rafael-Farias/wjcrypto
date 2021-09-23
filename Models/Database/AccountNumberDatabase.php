<?php

namespace WjCrypto\Models\Database;

use Monolog\Logger;
use PDO;
use WjCrypto\Helpers\CryptografyHelper;
use WjCrypto\Helpers\JsonResponse;
use WjCrypto\Helpers\LogHelper;
use WjCrypto\Helpers\ResponseArray;
use WjCrypto\Models\Entities\AccountNumber;

class AccountNumberDatabase extends Database
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
     * @param int $userId
     * @param int $accountNumber
     * @param int|null $legalPersonAccountId
     * @param int|null $naturalPersonAccountId
     */
    public function insert(
        int $userId,
        int $accountNumber,
        int $legalPersonAccountId = null,
        int $naturalPersonAccountId = null
    ): bool {
        $encryptedAccountNumber = $this->encrypt($accountNumber);
        try {
            $sqlQuery = "INSERT INTO accounts_number (`user_id`, `account_number`, `legal_person_account_id`, `natural_person_account_id`) VALUES (:user_id, :account_number, :legal_person_account_id, :natural_person_account_id);";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':user_id', $userId);
            $statement->bindParam(':account_number', $encryptedAccountNumber);
            $statement->bindParam(':legal_person_account_id', $legalPersonAccountId, PDO::PARAM_INT);
            $statement->bindParam(':natural_person_account_id', $naturalPersonAccountId, PDO::PARAM_INT);
            $statement->execute();
            return true;
        } catch (\PDOException $exception) {
            $message = 'PDO error on method WjCrypto\Models\Database\AccountNumberDatabase\insert: ' . $exception->getMessage(
                );
            $this->registerLog($message, 'database', 'AccountNumberDatabase', Logger::ERROR);
            $this->sendJsonMessage(
                'An error occurred while processing your request. Contact the system administrator.',
                500
            );
        }
        return false;
    }

    /**
     * @return AccountNumber[]|bool
     */
    public function selectAll(): bool|array
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
            $message = 'PDO error on method WjCrypto\Models\Database\AccountNumberDatabase\selectAll: ' . $exception->getMessage(
                );
            $this->registerLog($message, 'database', 'AccountNumberDatabase', Logger::ERROR);
            $this->sendJsonMessage(
                'An error occurred while processing your request. Contact the system administrator.',
                500
            );
        }
        return false;
    }

    /**
     * @param int $accountNumber
     * @return AccountNumber|bool
     */
    public function selectByAccountNumber(int $accountNumber): AccountNumber|bool
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
                return false;
            }
            $decryptedRow = $this->decryptRow($row);
            return $this->createAccountNumberObject($decryptedRow);
        } catch (\PDOException $exception) {
            $message = 'PDO error on method WjCrypto\Models\Database\AccountNumberDatabase\selectByAccountNumber: ' . $exception->getMessage(
                );
            $this->registerLog($message, 'database', 'AccountNumberDatabase', Logger::ERROR);
            $this->sendJsonMessage(
                'An error occurred while processing your request. Contact the system administrator.',
                500
            );
        }
        return false;
    }

    /**
     * @param int $userId
     * @return AccountNumber|bool
     */
    public function selectByUserId(int $userId): AccountNumber|bool
    {
        try {
            $sqlQuery = "SELECT * FROM accounts_number where user_id=:user_id;";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $statement->execute();
            $statement->setFetchMode(PDO::FETCH_ASSOC);
            $row = $statement->fetch();
            if ($row === false) {
                return false;
            }
            $decryptedRow = $this->decryptRow($row);
            return $this->createAccountNumberObject($decryptedRow);
        } catch (\PDOException $exception) {
            $message = 'PDO error on method WjCrypto\Models\Database\AccountNumberDatabase\selectByUserId: ' . $exception->getMessage(
                );
            $this->registerLog($message, 'database', 'AccountNumberDatabase', Logger::ERROR);
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