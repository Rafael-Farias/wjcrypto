<?php

namespace WjCrypto\Models\Services;

use WjCrypto\Helpers\ResponseArray;
use WjCrypto\Helpers\SanitizeString;
use WjCrypto\Models\Database\AddressDatabase;
use WjCrypto\Models\Database\CityDatabase;
use WjCrypto\Models\Database\StateDatabase;
use WjCrypto\Models\Entities\Address;

class AddressService
{
    use ResponseArray, SanitizeString;

    /**
     * @param string $state
     * @param string $city
     * @param string $address
     * @param string $addressComplement
     * @return Address | array
     */
    public function persistAddress(
        string $state,
        string $city,
        string $address,
        string $addressComplement
    ): Address {
        $state = $this->sanitizeString($state);
        $city = $this->sanitizeString($city);

        $stateDatabase = new StateDatabase();
        $statesInDatabase = $stateDatabase->selectAll();
        if (is_string($statesInDatabase)) {
            return $this->generateResponseArray($statesInDatabase, 500);
        }
        $stateId = 0;

        foreach ($statesInDatabase as $stateInDatabase) {
            $sanitizedStateName = $this->sanitizeString($stateInDatabase->getName());
            if ($sanitizedStateName === $state) {
                $stateId = $stateInDatabase->getId();
            }
        }
        $cityDatabase = new CityDatabase();
        $selectedCitiesByStateId = $cityDatabase->selectAllByState($stateId);
        if (is_string($selectedCitiesByStateId)) {
            return $this->generateResponseArray($selectedCitiesByStateId, 500);
        }
        $cityId = 0;

        foreach ($selectedCitiesByStateId as $cityInDatabase) {
            $sanitizedCityName = $this->sanitizeString($cityInDatabase->getName());
            if ($sanitizedCityName === $city) {
                $cityId = $cityInDatabase->getId();
            }
        }

        $addressDatabase = new AddressDatabase();
        $persistAddressResult = $addressDatabase->insert(
            $address,
            $addressComplement,
            $cityId
        );

        if (is_string($persistAddressResult)) {
            return $this->generateResponseArray($persistAddressResult, 500);
        }

        $addressInDatabase = $addressDatabase->selectByAddress($address);
        if (is_string($addressInDatabase)) {
            return $this->generateResponseArray($addressInDatabase, 500);
        }

        return $addressInDatabase;
    }

}