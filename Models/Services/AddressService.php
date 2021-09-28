<?php

namespace WjCrypto\Models\Services;

use WjCrypto\Helpers\JsonResponse;
use WjCrypto\Helpers\ResponseArray;
use WjCrypto\Helpers\SanitizeString;
use WjCrypto\Models\Database\AddressDatabase;
use WjCrypto\Models\Database\CityDatabase;
use WjCrypto\Models\Database\StateDatabase;
use WjCrypto\Models\Entities\Address;

class AddressService
{
    use ResponseArray;
    use SanitizeString;
    use JsonResponse;

    /**
     * @param string $state
     * @param string $city
     * @param string $address
     * @param string $addressComplement
     * @return bool
     */
    public function persistAddress(
        string $state,
        string $city,
        string $address,
        string $addressComplement
    ): bool {
        $state = $this->sanitizeString($state);
        $city = $this->sanitizeString($city);

        $stateDatabase = new StateDatabase();
        $statesInDatabase = $stateDatabase->selectAll();
        if ($statesInDatabase === false) {
            $this->sendJsonMessage('Error! Could not find the specified state.', 400);
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
        if ($selectedCitiesByStateId === false) {
            $this->sendJsonMessage('Error! Could not find the specified city in the database.', 400);
        }
        $cityId = 0;

        foreach ($selectedCitiesByStateId as $cityInDatabase) {
            $sanitizedCityName = $this->sanitizeString($cityInDatabase->getName());
            if ($sanitizedCityName === $city) {
                $cityId = $cityInDatabase->getId();
            }
        }

        $addressDatabase = new AddressDatabase();
        return $addressDatabase->insert(
            $address,
            $addressComplement,
            $cityId
        );
    }

    /**
     * @param string $addressName
     * @return Address
     */
    public function selectAddressByAddressName(string $addressName): Address
    {
        $addressDatabase = new AddressDatabase();
        $selectResult = $addressDatabase->selectByAddress($addressName);
        if ($selectResult === false) {
            $this->sendJsonMessage('Address not found in the database', 400);
        }
        return $selectResult;
    }

    /**
     * @param int $addressId
     */
    public function deleteAddress(int $addressId): void
    {
        $addressDatabase = new AddressDatabase();
        $addressDatabase->delete($addressId);
    }
}
