<?php

namespace WjCrypto\Models\Database;

use DI\Container;
use PDO;
use PDOException;
use WjCrypto\Helpers\CryptografyHelper;
use WjCrypto\Models\Entities\LegalPersonAccount;

class LegalPersonAccountDatabase extends Database
{
    use CryptografyHelper;

    private PDO $connection;

    public function __construct()
    {
        $this->connection = static::getConnection();
    }

    /**
     * @param string $name
     * @param string $cnpj
     * @param string $companyRegister
     * @param string $foundationDate
     * @param string $balance
     * @param int $addressId
     * @return bool|string
     */
    public function insert(
        string $name,
        string $cnpj,
        string $companyRegister,
        string $foundationDate,
        string $balance,
        int $addressId
    ): bool|string {
        $encryptedName = $this->encrypt($name);
        $encryptedCnpj = $this->encrypt($cnpj);
        $encryptedCompanyRegister = $this->encrypt($companyRegister);
        $encryptedFoundationDate = $this->encrypt($foundationDate);
        $encryptedBalance = $this->encrypt($balance);
        try {
            $sqlQuery = "INSERT INTO legal_person_accounts (`name`, `cnpj`, `company_register`, `foundation_date`, `balance`, `address_id`) VALUES (:name, :cnpj, :company_register, :foundation_date, :balance, :address_id);";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':name', $encryptedName);
            $statement->bindParam(':cnpj', $encryptedCnpj);
            $statement->bindParam(':company_register', $encryptedCompanyRegister);
            $statement->bindParam(':foundation_date', $encryptedFoundationDate);
            $statement->bindParam(':balance', $encryptedBalance);
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
    public function selectByCnpj(string $cnpj): LegalPersonAccount|string
    {
        $encryptedCnpj = $this->encrypt($cnpj);
        try {
            $sqlQuery = "SELECT * FROM legal_person_accounts WHERE cnpj=:cnpj;";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':cnpj', $encryptedCnpj);
            if ($statement->execute()) {
                $statement->setFetchMode(PDO::FETCH_ASSOC);
                $row = $statement->fetch();
                $decryptedRow = $this->decryptRow($row);
                return $this->createLegalPersonAccountObject($decryptedRow);
            }
            $errorArray = $statement->errorInfo();
            return $errorArray[2] . ' SQLSTATE error code: ' . $errorArray[0] . ' Driver error code: ' . $errorArray[1];
        } catch (\PDOException $exception) {
            return 'PDO error on method WjCrypto\Models\Database\UserDatabase\selectById: ' . $exception->getMessage();
        }
    }

    /**
     * @param int $id
     * @return LegalPersonAccount|string
     */
    public function selectById(int $id): LegalPersonAccount|string
    {
        try {
            $sqlQuery = "SELECT * FROM legal_person_accounts WHERE id=:id;";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':id', $id);
            if ($statement->execute()) {
                $statement->setFetchMode(PDO::FETCH_ASSOC);
                $row = $statement->fetch();
                $decryptedRow = $this->decryptRow($row);
                return $this->createLegalPersonAccountObject($decryptedRow);
            }
            $errorArray = $statement->errorInfo();
            return $errorArray[2] . ' SQLSTATE error code: ' . $errorArray[0] . ' Driver error code: ' . $errorArray[1];
        } catch (\PDOException $exception) {
            return 'PDO error on method WjCrypto\Models\Database\UserDatabase\selectById: ' . $exception->getMessage();
        }
    }

    /**
     * @param string $balance
     * @param int $id
     * @return bool|string
     */
    public function updateAccountBalance(string $balance, int $id): bool|string
    {
        $encryptedBalance = $this->encrypt($balance);
        try {
            $sqlQuery = "UPDATE legal_person_accounts SET balance=:balance WHERE id=:id;";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':balance', $encryptedBalance);
            $statement->bindParam(':id', $id, PDO::PARAM_INT);
            return $statement->execute();
        } catch (\PDOException $exception) {
            return 'PDO error on method WjCrypto\Models\Database\UserDatabase\selectById: ' . $exception->getMessage();
        }
    }

    /**
     * @param array $associativeArray
     * @return array
     */
    private function decryptRow(array $associativeArray): array
    {
        $associativeArray['name'] = $this->decrypt($associativeArray['name']);
        $associativeArray['cnpj'] = $this->decrypt($associativeArray['cnpj']);
        $associativeArray['company_register'] = $this->decrypt($associativeArray['company_register']);
        $associativeArray['foundation_date'] = $this->decrypt($associativeArray['foundation_date']);
        $associativeArray['balance'] = $this->decrypt($associativeArray['balance']);
        return $associativeArray;
    }

    /**
     * @param array $associativeArray
     * @return LegalPersonAccount
     */
    private function createLegalPersonAccountObject(array $associativeArray): LegalPersonAccount
    {
        $container = new Container();
        $legalPersonAccount = $container->get(LegalPersonAccount::class);
        $legalPersonAccount->setId($associativeArray['id']);
        $legalPersonAccount->setName($associativeArray['name']);
        $legalPersonAccount->setCnpj($associativeArray['cnpj']);
        $legalPersonAccount->setCompanyRegister($associativeArray['company_register']);
        $legalPersonAccount->setFoundationDate($associativeArray['foundation_date']);
        $legalPersonAccount->setBalance($associativeArray['balance']);
        $legalPersonAccount->setAddressId($associativeArray['address_id']);
        $legalPersonAccount->setCreationTimestamp($associativeArray['creation_timestamp']);
        $legalPersonAccount->setUpdateTimestamp($associativeArray['update_timestamp']);
        return $legalPersonAccount;
    }
}