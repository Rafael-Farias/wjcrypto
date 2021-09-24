<?php

use Pecee\SimpleRouter\SimpleRouter;

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../Config/routes.php';
require_once __DIR__ . '/../Config/helpers.php';

ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);

try {
    SimpleRouter::start();
} catch (Exception $exception) {
    response()->httpCode(404);
    response()->json(['message' => 'Page not found']);
}
