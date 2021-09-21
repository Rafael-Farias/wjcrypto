<?php

namespace WjCrypto\Models\Database;

use DI\Container;
use PDO;
use WjCrypto\Helpers\CryptografyHelper;
use WjCrypto\Models\Entities\NaturalPersonAccount;

class NaturalPersonAccountDatabase extends Database
{
    use CryptografyHelper;

    private PDO $connection;

    public function __construct()
    {
        $this->connection = static::getConnection();
    }

    /**
     * @param string $name
     * @param string $cpf
     * @param string $rg
     * @param string $birthDate
     * @param string $balance
     * @param int $addressId
     * @return bool|string
     */
    public function insert(
        string $name,
        string $cpf,
        string $rg,
        string $birthDate,
        string $balance,
        int $addressId
    ): bool|string {
        $encryptedName = $this->encrypt($name);
        $encryptedCpf = $this->encrypt($cpf);
        $encryptedRg = $this->encrypt($rg);
        $encryptedBirthDate = $this->encrypt($birthDate);
        $encryptedBalance = $this->encrypt($balance);
        try {
            $sqlQuery = "INSERT INTO natural_person_accounts (`name`, `cpf`, `rg`, `birth_date`, `balance`, `address_id`) VALUES (:name, :cpf, :rg, :birth_date, :balance, :address_id);";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':name', $encryptedName);
            $statement->bindParam(':cpf', $encryptedCpf);
            $statement->bindParam(':rg', $encryptedRg);
            $statement->bindParam(':birth_date', $encryptedBirthDate);
            $statement->bindParam(':balance', $encryptedBalance);
            $statement->bindParam(':address_id', $addressId);
            $statement->execute();
            return true;
        } catch (\PDOException $exception) {
            return 'PDO error on method WjCrypto\Models\Database\NaturalPersonAccountDatabase\insert: ' . $exception->getMessage(
                );
        }
    }

    /**
     * @param int $id
     * @return NaturalPersonAccount|string
     */
    public function selectById(int $id): string|NaturalPersonAccount
    {
        try {
            $sqlQuery = "SELECT * FROM natural_person_accounts WHERE id=:id;";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':id', $id);
            $statement->execute();
            $statement->setFetchMode(PDO::FETCH_ASSOC);
            $row = $statement->fetch();
            if ($row === false) {
                return $row;
            }
            $decryptedRow = $this->decryptRow($row);
            return $this->createLegalPersonAccountObject($decryptedRow);
        } catch (\PDOException $exception) {
            return 'PDO error on method WjCrypto\Models\Database\NaturalPersonAccountDatabase\selectById: ' . $exception->getMessage(
                );
        }
    }

    /**
     * @param string $cpf
     * @return NaturalPersonAccount|string
     */
    public function selectByCpf(string $cpf): string|NaturalPersonAccount
    {
        $encryptedCpf = $this->encrypt($cpf);
        try {
            $sqlQuery = "SELECT * FROM natural_person_accounts WHERE cpf=:cpf;";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':cpf', $encryptedCpf);
            $statement->execute();
            $statement->setFetchMode(PDO::FETCH_ASSOC);
            $row = $statement->fetch();
            if ($row === false) {
                return $row;
            }
            $decryptedRow = $this->decryptRow($row);
            return $this->createLegalPersonAccountObject($decryptedRow);
        } catch (\PDOException $exception) {
            return 'PDO error on method WjCrypto\Models\Database\NaturalPersonAccountDatabase\selectByCpf: ' . $exception->getMessage(
                );
        }
    }

    public function updateAccountBalance(string $balance, int $id): bool|string
    {
        $encryptedBalance = $this->encrypt($balance);
        try {
            $sqlQuery = "UPDATE natural_person_accounts SET balance=:balance WHERE id=:id;";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':balance', $encryptedBalance);
            $statement->bindParam(':id', $id, PDO::PARAM_INT);
            return $statement->execute();
        } catch (\PDOException $exception) {
            return 'PDO error on method WjCrypto\Models\Database\NaturalPersonAccountDatabase\updateAccountBalance: ' . $exception->getMessage(
                );
        }
    }

    /**
     * @param array $associativeArray
     * @return array
     */
    private function decryptRow(array $associativeArray): array
    {
        $associativeArray['name'] = $this->decrypt($associativeArray['name']);
        $associativeArray['cpf'] = $this->decrypt($associativeArray['cpf']);
        $associativeArray['rg'] = $this->decrypt($associativeArray['rg']);
        $associativeArray['birth_date'] = $this->decrypt($associativeArray['birth_date']);
        $associativeArray['balance'] = $this->decrypt($associativeArray['balance']);
        return $associativeArray;
    }

    /**
     * @param array $associativeArray
     * @return NaturalPersonAccount
     */
    private function createLegalPersonAccountObject(array $associativeArray): NaturalPersonAccount
    {
        $container = new Container();
        $legalPersonAccount = $container->get(NaturalPersonAccount::class);
        $legalPersonAccount->setId($associativeArray['id']);
        $legalPersonAccount->setName($associativeArray['name']);
        $legalPersonAccount->setCpf($associativeArray['cpf']);
        $legalPersonAccount->setRg($associativeArray['rg']);
        $legalPersonAccount->setBirthDate($associativeArray['birth_date']);
        $legalPersonAccount->setBalance($associativeArray['balance']);
        $legalPersonAccount->setAddressId($associativeArray['address_id']);
        $legalPersonAccount->setCreationTimestamp($associativeArray['creation_timestamp']);
        $legalPersonAccount->setUpdateTimestamp($associativeArray['update_timestamp']);
        return $legalPersonAccount;
    }
}