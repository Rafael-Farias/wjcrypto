<?php

use Pecee\SimpleRouter\SimpleRouter;
use WjCrypto\Config\ConfigureCitiesAndStates;
use WjCrypto\Controllers\AccountController;
use WjCrypto\Controllers\TransactionsController;
use WjCrypto\Controllers\UsersController;
use WjCrypto\Middlewares\AuthMiddleware;

/**
 *  This route will encrypt and persist the states and cities from Brazil in the database. Use this route only once,
 *  when is needed to persist all the states and cities again or in a fresh install.
 */

SimpleRouter::get('/admin/set-states-and-cities', [ConfigureCitiesAndStates::class, 'persistCitiesAndStates']);


SimpleRouter::post('/user', [UsersController::class, 'create']);
SimpleRouter::group(['middleware' => AuthMiddleware::class], function () {
    SimpleRouter::get('/login', []);
    SimpleRouter::get('/users', [UsersController::class, 'showAll']);
    SimpleRouter::get('/user/{id}', [UsersController::class, 'show']);
    SimpleRouter::delete('/user/{id}', [UsersController::class, 'delete']);
    SimpleRouter::put('/user', [UsersController::class, 'update']);

    SimpleRouter::get('/account', [AccountController::class, 'getAccountData']);
    SimpleRouter::post('/natural-person/create', [AccountController::class, 'createNaturalPersonAccount']);
    SimpleRouter::post('/legal-person/create', [AccountController::class, 'createLegalPersonAccount']);

    SimpleRouter::group(['prefix' => '/transactions'], function () {
        SimpleRouter::post('/deposit', [TransactionsController::class, 'deposit']);
        SimpleRouter::post('/withdraw', [TransactionsController::class, 'withdraw']);
        SimpleRouter::post('/transfer', [TransactionsController::class, 'transfer']);
    });
});
