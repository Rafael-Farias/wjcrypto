<?php

namespace WjCrypto\Models\Database;

use DI\Container;
use Monolog\Logger;
use PDO;
use PDOException;
use WjCrypto\Helpers\CryptografyHelper;
use WjCrypto\Helpers\JsonResponse;
use WjCrypto\Helpers\LogHelper;
use WjCrypto\Models\Entities\NaturalPersonAccount;

class NaturalPersonAccountDatabase extends Database
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
     * @param string $name
     * @param string $cpf
     * @param string $rg
     * @param string $birthDate
     * @param string $balance
     * @param int $addressId
     * @return bool
     */
    public function insert(
        string $name,
        string $cpf,
        string $rg,
        string $birthDate,
        string $balance,
        int $addressId
    ): bool {
        $encryptedName = $this->encrypt($name);
        $encryptedCpf = $this->encrypt($cpf);
        $encryptedRg = $this->encrypt($rg);
        $encryptedBirthDate = $this->encrypt($birthDate);
        $encryptedBalance = $this->encrypt($balance);
        try {
            $sqlQuery = "INSERT INTO natural_person_accounts " .
                "(`name`, `cpf`, `rg`, `birth_date`, `balance`, `address_id`) " .
                "VALUES (:name, :cpf, :rg, :birth_date, :balance, :address_id);";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':name', $encryptedName);
            $statement->bindParam(':cpf', $encryptedCpf);
            $statement->bindParam(':rg', $encryptedRg);
            $statement->bindParam(':birth_date', $encryptedBirthDate);
            $statement->bindParam(':balance', $encryptedBalance);
            $statement->bindParam(':address_id', $addressId);
            $statement->execute();
            return true;
        } catch (PDOException $exception) {
            $message = 'PDO error on method WjCrypto\Models\Database\NaturalPersonAccountDatabase\insert: ' .
                $exception->getMessage();
            $this->registerLog($message, 'database', 'NaturalPersonAccountDatabase', Logger::ERROR);
            $this->sendJsonMessage(
                'An error occurred while processing your request. Contact the system administrator.',
                500
            );
        }
        return false;
    }

    /**
     * @param int $id
     * @return NaturalPersonAccount|bool
     */
    public function selectById(int $id): NaturalPersonAccount|bool
    {
        try {
            $sqlQuery = "SELECT * FROM natural_person_accounts WHERE id=:id;";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':id', $id);
            $statement->execute();
            $statement->setFetchMode(PDO::FETCH_ASSOC);
            $row = $statement->fetch();
            if ($row === false) {
                return false;
            }
            $decryptedRow = $this->decryptRow($row);
            return $this->createLegalPersonAccountObject($decryptedRow);
        } catch (PDOException $exception) {
            $message = 'PDO error on method WjCrypto\Models\Database\NaturalPersonAccountDatabase\selectById: ' .
                $exception->getMessage();
            $this->registerLog($message, 'database', 'NaturalPersonAccountDatabase', Logger::ERROR);
            $this->sendJsonMessage(
                'An error occurred while processing your request. Contact the system administrator.',
                500
            );
        }
        return false;
    }

    /**
     * @param string $cpf
     * @return bool|NaturalPersonAccount
     */
    public function selectByCpf(string $cpf): bool|NaturalPersonAccount
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
                return false;
            }
            $decryptedRow = $this->decryptRow($row);
            return $this->createLegalPersonAccountObject($decryptedRow);
        } catch (PDOException $exception) {
            $message = 'PDO error on method WjCrypto\Models\Database\NaturalPersonAccountDatabase\selectByCpf: ' .
                $exception->getMessage();
            $this->registerLog($message, 'database', 'NaturalPersonAccountDatabase', Logger::ERROR);
            $this->sendJsonMessage(
                'An error occurred while processing your request. Contact the system administrator.',
                500
            );
        }
        return false;
    }

    /**
     * @param string $balance
     * @param int $id
     * @return bool
     */
    public function updateAccountBalance(string $balance, int $id): bool
    {
        $encryptedBalance = $this->encrypt($balance);
        try {
            $sqlQuery = "UPDATE natural_person_accounts SET balance=:balance WHERE id=:id;";
            $statement = $this->connection->prepare($sqlQuery);
            $statement->bindParam(':balance', $encryptedBalance);
            $statement->bindParam(':id', $id, PDO::PARAM_INT);
            $statement->execute();
            return true;
        } catch (PDOException $exception) {
            $message =
                'PDO error on method WjCrypto\Models\Database\NaturalPersonAccountDatabase\updateAccountBalance: ' .
                $exception->getMessage();
            $this->registerLog($message, 'database', 'NaturalPersonAccountDatabase', Logger::ERROR);
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
        try {
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
        } catch (\Exception $exception) {
            $this->registerLog($exception->getMessage(), 'database', 'NaturalPersonAccountDatabase', Logger::ERROR);
        }
        return $legalPersonAccount;
    }
}
