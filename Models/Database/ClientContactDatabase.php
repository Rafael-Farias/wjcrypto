<?php

namespace WjCrypto\Models\Database;

use PDO;

class ClientContactDatabase extends Database
{
    private PDO $connection;

    public function __construct()
    {
        $this->connection = static::getConnection();
    }

    public function insert(string $telephone, int $legalPersonAccountId = null, int $naturalPersonAccountId = null)
    {
        try {
            $sqlQuery = "INSERT INTO clients_contacts (`legal_person_account_id`, `natural_person_account_id`, `telephone`) VALUES (:legal_person_account_id, :natural_person_account_id, :telephone);";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':telephone', $telephone);
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



}