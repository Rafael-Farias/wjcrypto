<?php

namespace WjCrypto\Models\Database;

use Monolog\Logger;
use PDO;
use PDOException;
use WjCrypto\Helpers\CryptografyHelper;
use WjCrypto\Helpers\JsonResponse;
use WjCrypto\Helpers\LogHelper;
use WjCrypto\Models\Entities\ClientContact;

class ClientContactDatabase extends Database
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
     * @param string $telephone
     * @param int|null $legalPersonAccountId
     * @param int|null $naturalPersonAccountId
     * @return bool
     */
    public function insert(
        string $telephone,
        int $legalPersonAccountId = null,
        int $naturalPersonAccountId = null
    ): bool {
        $encryptedTelephone = $this->encrypt($telephone);
        try {
            $sqlQuery = "INSERT INTO clients_contacts " .
                "(`legal_person_account_id`, `natural_person_account_id`, `telephone`)" .
                "VALUES(:legal_person_account_id, :natural_person_account_id, :telephone);";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':telephone', $encryptedTelephone);
            $statement->bindParam(':legal_person_account_id', $legalPersonAccountId, PDO::PARAM_INT);
            $statement->bindParam(':natural_person_account_id', $naturalPersonAccountId, PDO::PARAM_INT);
            $statement->execute();
            return true;
        } catch (PDOException $exception) {
            $message = 'PDO error on method WjCrypto\Models\Database\ClientContactDatabase\insert: ' .
                $exception->getMessage();
            $this->registerLog($message, 'database', 'ClientContactDatabase', Logger::ERROR);
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
        $associativeArray['telephone'] = $this->decrypt($associativeArray['telephone']);
        return $associativeArray;
    }

    /**
     * @param array $associativeArray
     * @return ClientContact
     */
    private function createClientContactObject(array $associativeArray): ClientContact
    {
        $accountNumber = new ClientContact();
        $accountNumber->setId($associativeArray['id']);
        $accountNumber->setLegalPersonAccountId($associativeArray['legal_person_account_id']);
        $accountNumber->setNaturalPersonAccountId($associativeArray['natural_person_account_id']);
        $accountNumber->setTelephone($associativeArray['telephone']);
        $accountNumber->setCreationTimestamp($associativeArray['creation_timestamp']);
        $accountNumber->setUpdateTimestamp($associativeArray['update_timestamp']);
        return $accountNumber;
    }
}
