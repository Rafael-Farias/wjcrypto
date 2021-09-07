<?php

namespace WjCrypto\Models\Services;

use WjCrypto\Helpers\ResponseArray;

class DepositService
{
    use ResponseArray;

    public function makeDepositIntoAccount()
    {
        $inputedValues = input()->all();

        $naturalPersonAccountService = new NaturalPersonAccountService();
        $account = $naturalPersonAccountService->generateNaturalPersonAccountObject($inputedValues['accountNumber']);
        var_dump($account);
        die();
        $validationResult = $this->validateDepositData($inputedValues);
        if (is_array($validationResult)) {
            return $validationResult;
        }

//        if ($accountToDeposit === false) {
//            $message = 'Error! Invalid account number';
//            return $this->generateResponseArray($message, 400);
//        }

//        var_dump($accountToDeposit);
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