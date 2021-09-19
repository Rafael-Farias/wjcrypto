<?php

namespace WjCrypto\Models\Database;

use PDO;
use WjCrypto\Helpers\CryptografyHelper;
use WjCrypto\Models\Entities\ClientContact;

class ClientContactDatabase extends Database
{
    use CryptografyHelper;

    private PDO $connection;

    public function __construct()
    {
        $this->connection = static::getConnection();
    }

    public function insert(string $telephone, int $legalPersonAccountId = null, int $naturalPersonAccountId = null)
    {
        $encryptedTelephone = $this->encrypt($telephone);
        try {
            $sqlQuery = "INSERT INTO clients_contacts (`legal_person_account_id`, `natural_person_account_id`, `telephone`) VALUES (:legal_person_account_id, :natural_person_account_id, :telephone);";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':telephone', $encryptedTelephone);
            $statement->bindParam(':legal_person_account_id', $legalPersonAccountId, PDO::PARAM_INT);
            $statement->bindParam(':natural_person_account_id', $naturalPersonAccountId, PDO::PARAM_INT);
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