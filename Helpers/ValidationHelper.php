<?php

namespace WjCrypto\Helpers;

use WjCrypto\Models\Database\AccountNumberDatabase;
use WjCrypto\Models\Services\UserService;

trait ValidationHelper
{
    use JsonResponse;

    public function validateInput(array $requiredFields, array $inputedData)
    {
        $numberOfInputedFields = count($inputedData);
        $numberOfRequiredFields = count($requiredFields);

        if ($numberOfInputedFields !== $numberOfRequiredFields) {
            $message = 'Error! Invalid number of fields. Please only enter the required fields: ';
            foreach ($requiredFields as $requiredField) {
                $message .= ' ' . $requiredField;
            }
            $message .= ' .';
            $this->sendJsonMessage($message, 400);
        }

        foreach ($requiredFields as $requiredField) {
            $isRequiredFieldInRequest = array_key_exists($requiredField, $inputedData);
            if ($isRequiredFieldInRequest === false) {
                $message = 'Error! The field ' . $requiredField . ' does not exists in the payload.';
                $this->sendJsonMessage($message, 400);
            }
        }

        foreach ($inputedData as $key => $field) {
            if (empty($field)) {
                $message = 'Error! The field ' . $key . ' is empty.';
                $this->sendJsonMessage($message, 400);
            }
            if ($key === 'contacts' && is_array($field) === false) {
                $message = 'Error! The field ' . $key . ' is not a array.';
                $this->sendJsonMessage($message, 400);
            }
            if ($key === 'contacts' && is_array($field)) {
                foreach ($field as $telephone) {
                    if (is_string($telephone) === false) {
                        $message = 'Error! One of the telephones in ' . $key . ' is not a string.';
                        $this->sendJsonMessage($message, 400);
                    }
                }
            }
            if ($key !== 'contacts' && is_string($field) === false) {
                $message = 'Error! The field ' . $key . ' is not a string.';
                $this->sendJsonMessage($message, 400);
            }
        }
    }

    public function validateMoneyFormat(string $value)
    {
        $moneyRegex = '/^[0-9]{1,3}(\.[0-9]{3})*(,[0-9]{2})$/';
        $regexResult = preg_match($moneyRegex, $value);
        if ($regexResult === 0) {
            $message = 'Error! You have inputed an invalid money format. Use the following format: xxx.xxx,xx';
            $this->sendJsonMessage($message, 400);
        }
    }

    /**
     * @param int $accountNumber
     */
    public function validateAccountNumber(int $accountNumber): void
    {
        $accountNumberDatabase = new AccountNumberDatabase();
        $selectAccountNumberResult = $accountNumberDatabase->selectByAccountNumber($accountNumber);
        if ($selectAccountNumberResult === false) {
            $message = 'Error! The account number is invalid.';
            $this->sendJsonMessage($message, 400);
        }
    }

    /**
     * @param int $targetAccountNumber
     * @return bool
     */
    public function loggedAccountEqualsTargetAccount(int $targetAccountNumber): bool
    {
        $this->validateAccountNumber($targetAccountNumber);
        $userService = new UserService();
        $loggedUserAccountNumber = $userService->getLoggedUserAccountNumber();

        if ($targetAccountNumber === $loggedUserAccountNumber) {
            return true;
        }
        return false;
    }

}