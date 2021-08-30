<?php

namespace WjCrypto\Models\Services;

use Bissolli\ValidadorCpfCnpj\CPF;
use CodeInc\StripAccents\StripAccents;
use WjCrypto\middlewares\AuthMiddleware;
use WjCrypto\Models\Database\AccountNumberDatabase;
use WjCrypto\Models\Database\AddressDatabase;
use WjCrypto\Models\Database\CityDatabase;
use WjCrypto\Models\Database\ClientContactDatabase;
use WjCrypto\Models\Database\NaturalPersonAccountDatabase;
use WjCrypto\Models\Database\StateDatabase;


class NaturalPersonAccountService
{
    public function createAccount()
    {
        $validationResult = $this->validateNewAccountData();
        if (is_array($validationResult)) {
            return $validationResult;
        }
        $newAccountData = input()->all();
        $newAccountData['city'] = $this->sanitizeString($newAccountData['city']);
        $newAccountData['state'] = $this->sanitizeString($newAccountData['state']);

        $stateDatabase = new StateDatabase();
        $selectAllStatesResult = $stateDatabase->selectAll();
        if (is_string($selectAllStatesResult)) {
            return $this->returnResponseArray($selectAllStatesResult, 500);
        }

        foreach ($selectAllStatesResult as $state) {
            $sanitizedStateName = $this->sanitizeString($state->getName());
            if ($sanitizedStateName === $newAccountData['state']) {
                $newAccountData['stateId'] = $state->getId();
            }
        }
        $cityDatabase = new CityDatabase();
        $selectCitiesByStateResult = $cityDatabase->selectAllByState($newAccountData['stateId']);
        if (is_string($selectCitiesByStateResult)) {
            return $this->returnResponseArray($selectCitiesByStateResult, 500);
        }

        foreach ($selectCitiesByStateResult as $city) {
            $sanitizedCityName = $this->sanitizeString($city->getName());
            if ($sanitizedCityName === $newAccountData['city']) {
                $newAccountData['cityId'] = $city->getId();
            }
        }

        $addressDatabase = new AddressDatabase();
        $persistAddressResult = $addressDatabase->insert(
            $newAccountData['address'],
            $newAccountData['addressComplement'],
            $newAccountData['cityId']
        );
        if (is_string($persistAddressResult)) {
            return $this->returnResponseArray($selectCitiesByStateResult, 500);
        }

        $address = $addressDatabase->selectByAddress($newAccountData['address']);
        if (is_string($address)) {
            return $this->returnResponseArray($address, 500);
        }

        $birthDate = \DateTime::createFromFormat('d/m/Y',$newAccountData['birthDate']);

        $naturalPersonAccountDatabase = new NaturalPersonAccountDatabase();
        $persistNaturalPersonAccountResult = $naturalPersonAccountDatabase->insert(
            $newAccountData['name'],
            $newAccountData['cpf'],
            $newAccountData['rg'],
            $birthDate->format('Y/m/d'),
            0,
            $address->getId()
        );

        if (is_string($persistNaturalPersonAccountResult)) {
            return $this->returnResponseArray($persistNaturalPersonAccountResult, 500);
        }

        $selectAccountByCpfResult = $naturalPersonAccountDatabase->selectByCpf($newAccountData['cpf']);
        if (is_string($selectAccountByCpfResult)) {
            return $this->returnResponseArray($selectAccountByCpfResult, 500);
        }

        $clientContactDatabase = new ClientContactDatabase();
        foreach ($newAccountData['contacts'] as $contact) {
            $persistContactResult = $clientContactDatabase->insert($contact, null, $selectAccountByCpfResult->getId());
            if (is_string($persistContactResult)) {
                return $this->returnResponseArray($persistContactResult, 500);
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
            return $this->returnResponseArray($accountNumberInsertResult, 500);
        }

        $message = 'User created successfully!';
        return $this->returnResponseArray($message,200);
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
                return $this->returnResponseArray($message, 400);
            }
        }

        foreach ($newAccountData as $key => $field) {
            if (empty($field)) {
                $message = 'Error! The field ' . $key . ' is empty.';
                return $this->returnResponseArray($message, 400);
            }
        }

        $cpfValidator = new CPF($newAccountData['cpf']);
        if ($cpfValidator->isValid() === false) {
            $message = 'Error! Please enter a valid CPF.';
            return $this->returnResponseArray($message, 400);
        }

        $dateRegex = '/^(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/([0-9]{4})$/';
        $matches = [];

        $pregMatchResult = preg_match($dateRegex, $newAccountData['birthDate'], $matches[]);
        if ($pregMatchResult !== 1) {
            $message = 'Error! Invalid birth date format. Please enter a date with the following pattern: DD/MM/YYYY';
            return $this->returnResponseArray($message, 400);
        }

        $checkDateResult = checkdate($matches[0][2], $matches[0][1], $matches[0][3]);
        if ($checkDateResult === false) {
            $message = 'Error! Invalid date format. Please enter a valid date.';
            return $this->returnResponseArray($message, 400);
        }

        $telephoneRegex = '/^(\([0-9]{2}\)) (9?[0-9]{4})-([0-9]{4})$/';
        foreach ($newAccountData['contacts'] as $contact){
            $pregMatchResult = preg_match($telephoneRegex,$contact);
            if ($pregMatchResult !== 1){
                $message = 'Error! Invalid contact format. Please enter a telephone number with one of the following patterns: (xx) xxxxx-xxxx or (xx) xxxx-xxxx';
                return $this->returnResponseArray($message, 400);
            }
        }
        return null;
    }

    private function sanitizeString(string $stringToSanitize): string
    {
        $stringWithoutAccents = StripAccents::strip($stringToSanitize);
        $stringWithoutAccentsAndQuotationMark = preg_replace('/\'/', '', $stringWithoutAccents);
        return strtolower($stringWithoutAccentsAndQuotationMark);
    }

    private function returnResponseArray($message, int $httpResponseCode): array
    {
        if (is_string($message)) {
            $messageArray = ['message' => $message];
            return [
                'message' => $messageArray,
                'httpResponseCode' => $httpResponseCode
            ];
        }
        return [
            'message' => $message,
            'httpResponseCode' => $httpResponseCode
        ];
    }

    private function generateAccountNumber(string $userId)
    {
        $naturalPersonIdentifier = '01';
        $accountNumberDatabase = new AccountNumberDatabase();
        $allAccounts = $accountNumberDatabase->selectAll();
        $counter = count($allAccounts);
        return $naturalPersonIdentifier . $userId . $counter;
    }
}