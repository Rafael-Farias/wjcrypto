<?php

namespace WjCrypto\Models\Services;

use Bissolli\ValidadorCpfCnpj\CPF;
use DI\Container;
use WjCrypto\Helpers\ResponseArray;
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
    use ResponseArray;

    public function createAccount(): array
    {
        $validationResult = $this->validateNewAccountData();
        if (is_array($validationResult)) {
            return $validationResult;
        }
        $newAccountData = input()->all();
        $addressService = new AddressService();
        $address = $addressService->persistAddress(
            $newAccountData['state'],
            $newAccountData['city'],
            $newAccountData['address'],
            $newAccountData['addressComplement']
        );
        $birthDate = \DateTime::createFromFormat('d/m/Y', $newAccountData['birthDate']);

        $naturalPersonAccountDatabase = new NaturalPersonAccountDatabase();
        $persistNaturalPersonAccountResult = $naturalPersonAccountDatabase->insert(
            $newAccountData['name'],
            $newAccountData['cpf'],
            $newAccountData['rg'],
            $birthDate->format('Y/m/d'),
            '0',
            $address->getId()
        );

        if (is_string($persistNaturalPersonAccountResult)) {
            return $this->generateResponseArray($persistNaturalPersonAccountResult, 500);
        }

        $selectAccountByCpfResult = $naturalPersonAccountDatabase->selectByCpf($newAccountData['cpf']);
        if (is_string($selectAccountByCpfResult)) {
            return $this->generateResponseArray($selectAccountByCpfResult, 500);
        }

        $clientContactDatabase = new ClientContactDatabase();
        foreach ($newAccountData['contacts'] as $contact) {
            $persistContactResult = $clientContactDatabase->insert($contact, null, $selectAccountByCpfResult->getId());
            if (is_string($persistContactResult)) {
                return $this->generateResponseArray($persistContactResult, 500);
            }
        }

        $authMiddleware = new AuthMiddleware();
        $userId = $authMiddleware->getUserId();

        $accountNumber = $this->generateAccountNumber($userId);

        $accountNumberDatabase = new AccountNumberDatabase();
        $accountNumberInsertResult = $accountNumberDatabase->insert(
            $userId,
            $accountNumber,
            null,
            $selectAccountByCpfResult->getId()
        );
        if (is_string($accountNumberInsertResult)) {
            return $this->generateResponseArray($accountNumberInsertResult, 500);
        }

        $message = 'Account created successfully!';
        return $this->generateResponseArray($message, 200);
    }

    private function validateNewAccountData(): ?array
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
        $newAccountData = input()->all();

        foreach ($requiredFields as $requiredField) {
            $isRequiredFieldInRequest = array_key_exists($requiredField, $newAccountData);
            if ($isRequiredFieldInRequest === false) {
                $message = 'Error! The field ' . $requiredField . ' does not exists in the payload.';
                return $this->generateResponseArray($message, 400);
            }
        }

        foreach ($newAccountData as $key => $field) {
            if (empty($field)) {
                $message = 'Error! The field ' . $key . ' is empty.';
                return $this->generateResponseArray($message, 400);
            }
        }

        $cpfValidator = new CPF($newAccountData['cpf']);
        if ($cpfValidator->isValid() === false) {
            $message = 'Error! Please enter a valid CPF.';
            return $this->generateResponseArray($message, 400);
        }

        $dateRegex = '/^(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/([0-9]{4})$/';
        $matches = [];

        $pregMatchResult = preg_match($dateRegex, $newAccountData['birthDate'], $matches[]);
        if ($pregMatchResult !== 1) {
            $message = 'Error! Invalid birth date format. Please enter a date with the following pattern: DD/MM/YYYY';
            return $this->generateResponseArray($message, 400);
        }

        $checkDateResult = checkdate($matches[0][2], $matches[0][1], $matches[0][3]);
        if ($checkDateResult === false) {
            $message = 'Error! Invalid date format. Please enter a valid date.';
            return $this->generateResponseArray($message, 400);
        }

        $telephoneRegex = '/^(\([0-9]{2}\)) (9?[0-9]{4})-([0-9]{4})$/';
        foreach ($newAccountData['contacts'] as $contact) {
            $pregMatchResult = preg_match($telephoneRegex, $contact);
            if ($pregMatchResult !== 1) {
                $message = 'Error! Invalid contact format. Please enter a telephone number with one of the following patterns: (xx) xxxxx-xxxx or (xx) xxxx-xxxx';
                return $this->generateResponseArray($message, 400);
            }
        }
        return null;
    }

    private function generateAccountNumber(string $userId)
    {
        $naturalPersonIdentifier = '01';
        $accountNumberDatabase = new AccountNumberDatabase();
        $allAccounts = $accountNumberDatabase->selectAll();
        $counter = count($allAccounts);
        return $naturalPersonIdentifier . $userId . $counter;
    }

    public function generateNaturalPersonAccountObject(int $accountNumber)
    {
        $accountNumberDatabase = new AccountNumberDatabase();
        $accountNumber = $accountNumberDatabase->selectByAccountNumber($accountNumber);

        $naturalPersonAccountDatabase = new NaturalPersonAccountDatabase();
        $naturalPersonAccount = $naturalPersonAccountDatabase->selectById(
            $accountNumber->getNaturalPersonAccountId()
        );

        $naturalPersonAccount->setAccountNumber($accountNumber);

        $addressDatabase = new AddressDatabase();
        $accountAddress = $addressDatabase->selectById($naturalPersonAccount->getAddressId());
        $naturalPersonAccount->setAddress($accountAddress);

        $cityDatabase = new CityDatabase();
        $city = $cityDatabase->selectById($accountAddress->getCityId());
        $naturalPersonAccount->setCity($city);

        $stateDatabase = new StateDatabase();
        $state = $stateDatabase->selectById($city->getStateId());
        $naturalPersonAccount->setState($state);

        return $naturalPersonAccount;
    }

    public function updateBalance(string $newBalance, int $id)
    {
        $naturalPersonAccountDatabase = new NaturalPersonAccountDatabase();
        $result = $naturalPersonAccountDatabase->updateAccountBalance($newBalance, $id);
        if (is_string($result)) {
            return $this->generateResponseArray($result, 400);
        }
        return $result;
    }
}