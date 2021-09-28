<?php

namespace WjCrypto\Config;

use WjCrypto\Helpers\CryptografyHelper;
use WjCrypto\Helpers\JsonResponse;
use WjCrypto\Models\Database\CityDatabase;
use WjCrypto\Models\Database\StateDatabase;

class ConfigureCitiesAndStates
{
    use CryptografyHelper;
    use JsonResponse;

    public function persistCitiesAndStates()
    {
        $this->persistStates();
        $this->persistCities();
        $this->sendJsonMessage('Successfully inserted the cities and states!', 200);
    }

    private function persistCities()
    {
        $citiesWithStateInitials = require_once 'cities.php';
        foreach ($citiesWithStateInitials as $stateInitial => $cities) {
            foreach ($cities as $city) {
                $this->persistCity($city, $stateInitial);
            }
        }
    }

    private function persistCity(string $city, string $stateInitials)
    {
        $cityDatabase = new CityDatabase();
        $cityDatabase->insert($city, $stateInitials);
    }

    private function persistStates()
    {
        $states = require_once 'states.php';
        foreach ($states as $stateInitials => $stateName) {
            $this->persistState($stateName, $stateInitials);
        }
    }

    private function persistState(string $stateName, string $stateInitials)
    {
        $stateDatabase = new StateDatabase();
        $stateDatabase->insert($stateName, $stateInitials);
    }
}
