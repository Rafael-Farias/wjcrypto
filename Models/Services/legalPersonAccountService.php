<?php

namespace WjCrypto\Models\Services;

use Bissolli\ValidadorCpfCnpj\CNPJ;
use Thiagocfn\InscricaoEstadual\Util\Validador;
use WjCrypto\Helpers\JsonResponse;
use WjCrypto\Helpers\LogHelper;
use WjCrypto\Helpers\ResponseArray;
use WjCrypto\Helpers\SanitizeString;
use WjCrypto\Middlewares\AuthMiddleware;
use WjCrypto\Models\Database\AccountNumberDatabase;
use WjCrypto\Models\Database\AddressDatabase;
use WjCrypto\Models\Database\CityDatabase;
use WjCrypto\Models\Database\ClientContactDatabase;
use WjCrypto\Models\Database\LegalPersonAccountDatabase;
use WjCrypto\Models\Database\StateDatabase;
use WjCrypto\Models\Entities\AccountNumber;
use WjCrypto\Models\Entities\LegalPersonAccount;

class legalPersonAccountService
{
    use ResponseArray;
    use SanitizeString;
    use LogHelper;
    use JsonResponse;


    public function createAccount()
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

        $foundationDate = \DateTime::createFromFormat('d/m/Y', $newAccountData['foundationDate']);
        $legalPersonAccountDatabase = new legalPersonAccountDatabase();
        $legalPersonAccountDatabase->insert(
            $newAccountData['name'],
            $newAccountData['cnpj'],
            $newAccountData['companyRegister'],
            $foundationDate->format('Y/m/d'),
            0,
            $address->getId()
        );

        $selectAccountByCnpjResult = $legalPersonAccountDatabase->selectByCnpj($newAccountData['cnpj']);

        $clientContactDatabase = new ClientContactDatabase();
        foreach ($newAccountData['contacts'] as $contact) {
            $clientContactDatabase->insert($contact, $selectAccountByCnpjResult->getId(), null);
        }

        $authMiddleware = new AuthMiddleware();
        $userId = $authMiddleware->getUserId();

        $accountNumber = $this->generateAccountNumber($userId);

        $accountNumberDatabase = new AccountNumberDatabase();
        $accountNumberDatabase->insert(
            $userId,
            $accountNumber,
            $selectAccountByCnpjResult->getId(),
            null
        );
        $this->sendJsonMessage('Account created successfully!', 200);
    }

    /**
     * @param array $newAccountData
     */
    private function validateNewAccountData(array $newAccountData): void
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

        $authMiddleware = new AuthMiddleware();
        $userId = $authMiddleware->getUserId();
        $accountNumberDatabase = new AccountNumberDatabase();
        $selectResult = $accountNumberDatabase->selectByUserId($userId);
        if ($selectResult instanceof AccountNumber) {
            $message = 'The logged user already has an account.';
            $this->sendJsonMessage($message, 400);
        }

        foreach ($requiredFields as $requiredField) {
            $isRequiredFieldInRequest = array_key_exists($requiredField, $newAccountData);
            if ($isRequiredFieldInRequest === false) {
                $message = 'Error! The field ' . $requiredField . ' does not exists in the payload.';
                $this->sendJsonMessage($message, 400);
            }
        }

        foreach ($newAccountData as $key => $field) {
            if (empty($field)) {
                $message = 'Error! The field ' . $key . ' is empty.';
                $this->sendJsonMessage($message, 400);
            }
        }

        $cnpjValidator = new CNPJ($newAccountData['cnpj']);
        if ($cnpjValidator->isValid() === false) {
            $message = 'Error! Please enter a valid CNPJ.';
            $this->sendJsonMessage($message, 400);
        }

        $dateRegex = '/^(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/([0-9]{4})$/';
        $matches = [];

        $pregMatchResult = preg_match($dateRegex, $newAccountData['foundationDate'], $matches[]);
        if ($pregMatchResult !== 1) {
            $message = 'Error! Invalid foundation date format. Please enter a date with the following pattern: DD/MM/YYYY';
            $this->sendJsonMessage($message, 400);
        }

        $checkDateResult = checkdate($matches[0][2], $matches[0][1], $matches[0][3]);
        if ($checkDateResult === false) {
            $message = 'Error! Invalid date. Please enter a valid date.';
            $this->sendJsonMessage($message, 400);
        }

        $telephoneRegex = '/^(\([0-9]{2}\)) (9?[0-9]{4})-([0-9]{4})$/';
        foreach ($newAccountData['contacts'] as $contact) {
            $pregMatchResult = preg_match($telephoneRegex, $contact);
            if ($pregMatchResult !== 1) {
                $message = 'Error! Invalid contact format. Please enter a telephone number with one of the following patterns: (xx) xxxxx-xxxx or (xx) xxxx-xxxx';
                $this->sendJsonMessage($message, 400);
            }
        }

        $newAccountData['state'] = $this->sanitizeString($newAccountData['state']);
        $stateDatabase = new StateDatabase();
        $selectAllStatesResult = $stateDatabase->selectAll();
        if ($selectAllStatesResult === false) {
            $message = 'Error! Could not find the specified State in the database. Confirm if the State name is correct.';
            $this->sendJsonMessage($message, 400);
        }
        foreach ($selectAllStatesResult as $state) {
            $sanitizedStateName = $this->sanitizeString($state->getName());
            if ($sanitizedStateName === $newAccountData['state']) {
                $newAccountData['stateInitials'] = $state->getInitials();
                $newAccountData['stateId'] = $state->getId();
            }
        }
        if (array_key_exists('stateInitials', $newAccountData) === false) {
            $message = 'Error! Invalid state. Please enter a valid State.';
            $this->sendJsonMessage($message, 400);
        }

        $companyRegisterValidationResult = Validador::check(
            $newAccountData['stateInitials'],
            $newAccountData['companyRegister']
        );

        if ($companyRegisterValidationResult === false) {
            $message = 'Error! Invalid company register. Please enter a valid company register.';
            $this->sendJsonMessage($message, 400);
        }

        $newAccountData['city'] = $this->sanitizeString($newAccountData['city']);
        $cityDatabase = new CityDatabase();
        $citiesByStateId = $cityDatabase->selectAllByState($newAccountData['stateId']);

        if ($citiesByStateId === false) {
            $message = 'Error! Could not find the specified City in the database. Confirm if the City name is correct.';
            $this->sendJsonMessage($message, 400);
        }
        $foundCity = false;
        foreach ($citiesByStateId as $city) {
            $sanitizedCityName = $this->sanitizeString($city->getName());
            if ($sanitizedCityName === $newAccountData['city']) {
                $foundCity = true;
            }
        }
        if ($foundCity === false) {
            $message = 'Error! Invalid city. Please enter a valid city.';
            $this->sendJsonMessage($message, 400);
        }
    }

    /**
     * @param string $userId
     * @return string
     */
    private function generateAccountNumber(string $userId): string
    {
        $legalPersonIdentifier = '2';
        $accountNumberDatabase = new AccountNumberDatabase();
        $allAccounts = $accountNumberDatabase->selectAll();
        if ($allAccounts === false) {
            return $legalPersonIdentifier . $userId . 1;
        }
        $counter = count($allAccounts);
        $counter++;
        return $legalPersonIdentifier . $userId . $counter;
    }

    /**
     * @param int $accountNumber
     * @return LegalPersonAccount
     */
    public function generateLegalPersonAccountObject(int $accountNumber): LegalPersonAccount
    {
        $accountNumberDatabase = new AccountNumberDatabase();
        $accountNumber = $accountNumberDatabase->selectByAccountNumber($accountNumber);
        if ($accountNumber === false) {
            $this->sendJsonMessage('Error! The account number is invalid.', 400);
        }

        $legalPersonAccountDatabase = new LegalPersonAccountDatabase();
        $legalPersonAccount = $legalPersonAccountDatabase->selectById(
            $accountNumber->getLegalPersonAccountId()
        );
        if ($legalPersonAccount === false) {
            $this->sendJsonMessage('Error! Could not find the account.', 400);
        }

        $legalPersonAccount->setAccountNumber($accountNumber);

        $addressDatabase = new AddressDatabase();
        $accountAddress = $addressDatabase->selectById($legalPersonAccount->getAddressId());
        if ($accountAddress === false) {
            $this->sendJsonMessage('Error! Account address not found.', 400);
        }
        $legalPersonAccount->setAddress($accountAddress);

        $cityDatabase = new CityDatabase();
        $city = $cityDatabase->selectById($accountAddress->getCityId());
        if ($city === false) {
            $this->sendJsonMessage('Error! City attached to the address not found.', 400);
        }
        $legalPersonAccount->setCity($city);

        $stateDatabase = new StateDatabase();
        $state = $stateDatabase->selectById($city->getStateId());
        if ($state === false) {
            $this->sendJsonMessage('Error! State attached to the city not found.', 400);

        }
        $legalPersonAccount->setState($state);

        return $legalPersonAccount;
    }

    /**
     * @param string $newBalance
     * @param int $id
     */
    public function updateBalance(string $newBalance, int $id): void
    {
        $legalPersonAccountDatabase = new LegalPersonAccountDatabase();
        $updateResult = $legalPersonAccountDatabase->updateAccountBalance($newBalance, $id);
        if ($updateResult === false) {
            $this->sendJsonMessage('Error! Could not update the account balance.', 500);
        }
    }
}