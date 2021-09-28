<?php

namespace WjCrypto\Models\Services;

use Bissolli\ValidadorCpfCnpj\CPF;
use WjCrypto\Helpers\JsonResponse;
use WjCrypto\Helpers\LogHelper;
use WjCrypto\Helpers\SanitizeString;
use WjCrypto\Helpers\ValidationHelper;
use WjCrypto\Middlewares\AuthMiddleware;
use WjCrypto\Models\Database\AccountNumberDatabase;
use WjCrypto\Models\Database\AddressDatabase;
use WjCrypto\Models\Database\CityDatabase;
use WjCrypto\Models\Database\ClientContactDatabase;
use WjCrypto\Models\Database\NaturalPersonAccountDatabase;
use WjCrypto\Models\Database\StateDatabase;
use WjCrypto\Models\Entities\NaturalPersonAccount;

class NaturalPersonAccountService
{
    use SanitizeString;
    use LogHelper;
    use JsonResponse;
    use ValidationHelper;

    public function createAccount(): void
    {
        $newAccountData = input()->all();
        $this->validateNewAccountData($newAccountData);

        $addressService = new AddressService();
        $persistAddressResult = $addressService->persistAddress(
            $newAccountData['state'],
            $newAccountData['city'],
            $newAccountData['address'],
            $newAccountData['addressComplement']
        );
        if ($persistAddressResult === false) {
            $this->sendJsonMessage(
                'Error! Could not persist the address in the database. Try again or contact the system administrator.',
                500
            );
        }
        $address = $addressService->selectAddressByAddressName($newAccountData['address']);

        $birthDate = \DateTime::createFromFormat('d/m/Y', $newAccountData['birthDate']);
        $naturalPersonAccountDatabase = new NaturalPersonAccountDatabase();
        $naturalPersonAccountDatabase->insert(
            $newAccountData['name'],
            $newAccountData['cpf'],
            $newAccountData['rg'],
            $birthDate->format('Y/m/d'),
            '0',
            $address->getId()
        );

        $selectAccountByCpfResult = $naturalPersonAccountDatabase->selectByCpf($newAccountData['cpf']);

        $clientContactDatabase = new ClientContactDatabase();
        foreach ($newAccountData['contacts'] as $contact) {
            $clientContactDatabase->insert($contact, null, $selectAccountByCpfResult->getId());
        }

        $authMiddleware = new AuthMiddleware();
        $userId = $authMiddleware->getUserId();

        $accountNumber = $this->generateAccountNumber($userId);

        $accountNumberDatabase = new AccountNumberDatabase();
        $accountNumberDatabase->insert(
            $userId,
            $accountNumber,
            null,
            $selectAccountByCpfResult->getId()
        );

        $this->sendJsonMessage('Account created successfully!', 200);
    }

    /**
     * @param array $newAccountData
     */
    private function validateNewAccountData(array $newAccountData)
    {
        $requiredFields = [
            'name',
            'cpf',
            'rg',
            'birthDate',
            'address',
            'addressComplement',
            'contacts',
            'city',
            'state'
        ];

        $this->validateInput($requiredFields, $newAccountData);
        $this->validateAccountData($newAccountData);

        $cpfRegex = '/^[0-9]{3}.[0-9]{3}.[0-9]{3}-[0-9]{2}$/';
        $matches = [];

        $pregMatchResult = preg_match($cpfRegex, $newAccountData['cpf'], $matches[]);
        if ($pregMatchResult !== 1) {
            $message = 'Error! Invalid CPF format. Please enter a CPF with the following pattern: xxx.xxx.xxx-xx';
            $this->sendJsonMessage($message, 400);
        }

        $cpfValidator = new CPF($newAccountData['cpf']);
        if ($cpfValidator->isValid() === false) {
            $message = 'Error! Please enter a valid CPF.';
            $this->sendJsonMessage($message, 400);
        }

        $naturalPersonDatabase = new NaturalPersonAccountDatabase();
        $selectResult = $naturalPersonDatabase->selectByCpf($newAccountData['cpf']);
        if ($selectResult !== false) {
            $message = 'Error! Another user already uses this CPF.';
            $this->sendJsonMessage($message, 400);
        }

        $dateRegex = '/^(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/([0-9]{4})$/';
        $matches = [];

        $pregMatchResult = preg_match($dateRegex, $newAccountData['birthDate'], $matches[]);
        if ($pregMatchResult !== 1) {
            $message = 'Error! Invalid birth date format. Please enter a date with the following pattern: DD/MM/YYYY';
            $this->sendJsonMessage($message, 400);
        }

        $checkDateResult = checkdate($matches[0][2], $matches[0][1], $matches[0][3]);
        if ($checkDateResult === false) {
            $message = 'Error! Invalid date format. Please enter a valid date.';
            $this->sendJsonMessage($message, 400);
        }
    }

    /**
     * @param string $userId
     * @return string
     */
    private function generateAccountNumber(string $userId): string
    {
        $naturalPersonIdentifier = '1';
        $accountNumberDatabase = new AccountNumberDatabase();
        $allAccounts = $accountNumberDatabase->selectAll();
        if ($allAccounts === false) {
            return $naturalPersonIdentifier . $userId . 1;
        }
        $counter = count($allAccounts);
        $counter++;
        return $naturalPersonIdentifier . $userId . $counter;
    }

    /**
     * @param int $accountNumber
     * @return NaturalPersonAccount
     */
    public function generateNaturalPersonAccountObject(int $accountNumber): NaturalPersonAccount
    {
        $accountNumberDatabase = new AccountNumberDatabase();
        $accountNumber = $accountNumberDatabase->selectByAccountNumber($accountNumber);
        if ($accountNumber === false) {
            $this->sendJsonMessage('Error! The account number is invalid.', 400);
        }

        $naturalPersonAccountDatabase = new NaturalPersonAccountDatabase();
        $naturalPersonAccount = $naturalPersonAccountDatabase->selectById(
            $accountNumber->getNaturalPersonAccountId()
        );
        if ($naturalPersonAccount === false) {
            $this->sendJsonMessage('Error! Could not find the account.', 400);
        }

        $naturalPersonAccount->setAccountNumber($accountNumber);

        $addressDatabase = new AddressDatabase();
        $accountAddress = $addressDatabase->selectById($naturalPersonAccount->getAddressId());
        if ($accountAddress === false) {
            $this->sendJsonMessage('Error! Account address not found.', 400);
        }
        $naturalPersonAccount->setAddress($accountAddress);

        $cityDatabase = new CityDatabase();
        $city = $cityDatabase->selectById($accountAddress->getCityId());
        if ($city === false) {
            $this->sendJsonMessage('Error! City attached to the address not found.', 400);
        }
        $naturalPersonAccount->setCity($city);

        $stateDatabase = new StateDatabase();
        $state = $stateDatabase->selectById($city->getStateId());
        if ($state === false) {
            $this->sendJsonMessage('Error! State attached to the city not found.', 400);
        }
        $naturalPersonAccount->setState($state);

        return $naturalPersonAccount;
    }

    /**
     * @param string $newBalance
     * @param int $id
     */
    public function updateBalance(string $newBalance, int $id): void
    {
        $naturalPersonAccountDatabase = new NaturalPersonAccountDatabase();
        $updateResult = $naturalPersonAccountDatabase->updateAccountBalance($newBalance, $id);
        if ($updateResult === false) {
            $this->sendJsonMessage('Error! Could not update the account balance.', 500);
        }
    }
}
