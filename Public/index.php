<?php

use Pecee\SimpleRouter\SimpleRouter;

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../Config/routes.php';
require_once __DIR__ . '/../Config/helpers.php';

try {
    SimpleRouter::start();
} catch (Exception $exception) {
    var_export($exception);
}
