<?php

namespace WjCrypto\Models\Services;

use WjCrypto\Helpers\ResponseArray;
use WjCrypto\Models\Database\AccountNumberDatabase;

class DepositService
{
    use ResponseArray;

    public function makeDepositIntoAccount()
    {
        $inputedValues = input()->all();
        $validationResult = $this->validateDepositData($inputedValues);
        if (is_array($validationResult)) {
            return $validationResult;
        }
        $accountDatabase = new AccountNumberDatabase();
        $accountDatabase->selectAll();
    }

    private function validateDepositData(array $inputedValues): ?array
    {
        $requiredFields = [
            'accountNumber',
            'depositValue'
        ];

        $numberOfInputedFields = count($inputedValues);
        $numberOfRequiredFields = count($requiredFields);

        if ($numberOfInputedFields !== $numberOfRequiredFields) {
            $message = 'Error! Invalid number of fields. Please only enter the required fields: accountNumber and depositValue.';
            return $this->generateResponseArray($message, 400);
        }

        foreach ($requiredFields as $requiredField) {
            $isRequiredFieldInRequest = array_key_exists($requiredField, $inputedValues);
            if ($isRequiredFieldInRequest === false) {
                $message = 'Error! The field ' . $requiredField . ' does not exists in the payload.';
                return $this->generateResponseArray($message, 400);
            }
        }

        foreach ($inputedValues as $key => $field) {
            if (empty($field)) {
                $message = 'Error! The field ' . $key . ' is empty.';
                return $this->generateResponseArray($message, 400);
            }
            if (is_numeric($field) === false) {
                $message = 'Error! The field ' . $key . ' is not a number.';
                return $this->generateResponseArray($message, 400);
            }
        }
        return null;
    }
}