<?php

namespace WjCrypto\Models\Services;

use Bissolli\ValidadorCpfCnpj\CNPJ;
use DI\Container;
use Thiagocfn\InscricaoEstadual\Util\Validador;
use WjCrypto\Helpers\ResponseArray;
use WjCrypto\Helpers\SanitizeString;
use WjCrypto\Middlewares\AuthMiddleware;
use WjCrypto\Models\Database\AccountNumberDatabase;
use WjCrypto\Models\Database\AddressDatabase;
use WjCrypto\Models\Database\CityDatabase;
use WjCrypto\Models\Database\ClientContactDatabase;
use WjCrypto\Models\Database\LegalPersonAccountDatabase;
use WjCrypto\Models\Database\StateDatabase;
use WjCrypto\Models\Entities\LegalPersonAccount;

class legalPersonAccountService
{
    use ResponseArray, SanitizeString;

    public function createAccount()
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
        $foundationDate = \DateTime::createFromFormat('d/m/Y', $newAccountData['foundationDate']);
        $legalPersonAccountDatabase = new legalPersonAccountDatabase();
        $persistLegalPersonAccountResult = $legalPersonAccountDatabase->insert(
            $newAccountData['name'],
            $newAccountData['cnpj'],
            $newAccountData['companyRegister'],
            $foundationDate->format('Y/m/d'),
            0,
            $address->getId()
        );

        if (is_string($persistLegalPersonAccountResult)) {
            return $this->generateResponseArray($persistLegalPersonAccountResult, 500);
        }

        $selectAccountByCnpjResult = $legalPersonAccountDatabase->selectByCnpj($newAccountData['cnpj']);
        if (is_string($selectAccountByCnpjResult)) {
            return $this->generateResponseArray($selectAccountByCnpjResult, 500);
        }

        $clientContactDatabase = new ClientContactDatabase();
        foreach ($newAccountData['contacts'] as $contact) {
            $persistContactResult = $clientContactDatabase->insert($contact, $selectAccountByCnpjResult->getId(), null);
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
            $selectAccountByCnpjResult->getId(),
            null
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
            'cnpj',
            'companyRegister',
            'foundationDate',
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

        $cnpjValidator = new CNPJ($newAccountData['cnpj']);
        if ($cnpjValidator->isValid() === false) {
            $message = 'Error! Please enter a valid CNPJ.';
            return $this->generateResponseArray($message, 400);
        }

        $dateRegex = '/^(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/([0-9]{4})$/';
        $matches = [];

        $pregMatchResult = preg_match($dateRegex, $newAccountData['foundationDate'], $matches[]);
        if ($pregMatchResult !== 1) {
            $message = 'Error! Invalid foundation date format. Please enter a date with the following pattern: DD/MM/YYYY';
            return $this->generateResponseArray($message, 400);
        }

        $checkDateResult = checkdate($matches[0][2], $matches[0][1], $matches[0][3]);
        if ($checkDateResult === false) {
            $message = 'Error! Invalid date. Please enter a valid date.';
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

        $newAccountData['state'] = $this->sanitizeString($newAccountData['state']);
        $stateDatabase = new StateDatabase();
        $selectAllStatesResult = $stateDatabase->selectAll();
        foreach ($selectAllStatesResult as $state) {
            $sanitizedStateName = $this->sanitizeString($state->getName());
            if ($sanitizedStateName === $newAccountData['state']) {
                $newAccountData['stateInitials'] = $state->getInitials();
                $newAccountData['stateId'] = $state->getId();
            }
        }
        if (array_key_exists('stateInitials', $newAccountData) === false) {
            $message = 'Error! Invalid state. Please enter a valid state.';
            return $this->generateResponseArray($message, 400);
        }
        $companyRegisterValidationResult = Validador::check(
            $newAccountData['stateInitials'],
            $newAccountData['companyRegister']
        );
        if ($companyRegisterValidationResult === false) {
            $message = 'Error! Invalid company register. Please enter a valid company register.';
            return $this->generateResponseArray($message, 400);
        }
        $newAccountData['city'] = $this->sanitizeString($newAccountData['city']);
        $cityDatabase = new CityDatabase();
        $citiesByStateId = $cityDatabase->selectAllByState($newAccountData['stateId']);
        $foundCity = false;
        foreach ($citiesByStateId as $city) {
            $sanitizedCityName = $this->sanitizeString($city->getName());
            if ($sanitizedCityName === $newAccountData['city']) {
                $foundCity = true;
            }
        }
        if ($foundCity === false) {
            $message = 'Error! Invalid city. Please enter a valid city.';
            return $this->generateResponseArray($message, 400);
        }
        return null;
    }

    private function generateAccountNumber(string $userId)
    {
        $legalPersonIdentifier = '02';
        $accountNumberDatabase = new AccountNumberDatabase();
        $allAccounts = $accountNumberDatabase->selectAll();
        $counter = count($allAccounts);
        return $legalPersonIdentifier . $userId . $counter;
    }

    public function generateLegalPersonAccountObject(int $accountNumber)
    {
        $accountNumberDatabase = new AccountNumberDatabase();
        $accountNumber = $accountNumberDatabase->selectByAccountNumber($accountNumber);

        $legalPersonAccountDatabase = new LegalPersonAccountDatabase();
        $legalPersonAccount = $legalPersonAccountDatabase->selectById(
            $accountNumber->getLegalPersonAccountId()
        );

        $legalPersonAccount->setAccountNumber($accountNumber);

        $addressDatabase = new AddressDatabase();
        $accountAddress = $addressDatabase->selectById($legalPersonAccount->getAddressId());
        $legalPersonAccount->setAddress($accountAddress);

        $cityDatabase = new CityDatabase();
        $city = $cityDatabase->selectById($accountAddress->getCityId());
        $legalPersonAccount->setCity($city);

        $stateDatabase = new StateDatabase();
        $state = $stateDatabase->selectById($city->getStateId());
        $legalPersonAccount->setState($state);

        return $legalPersonAccount;
    }

    public function updateBalance(string $newBalance, int $id)
    {
        $legalPersonAccountDatabase = new LegalPersonAccountDatabase();
        $result = $legalPersonAccountDatabase->updateAccountBalance($newBalance, $id);
        if (is_string($result)) {
            return $this->generateResponseArray($result, 400);
        }
        return $result;
    }
}