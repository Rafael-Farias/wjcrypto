<?php

use Pecee\SimpleRouter\SimpleRouter;
use WjCrypto\Config\ConfigureCitiesAndStates;
use WjCrypto\Controllers\AccountController;
use WjCrypto\Controllers\TransactionsController;
use WjCrypto\Controllers\UsersController;
use WjCrypto\Middlewares\AuthMiddleware;

/**
 *  This route will encrypt and persist the states and cities from Brazil in the database. Use this route only once, when is needed to persist all the states and cities again or in a fresh install.
 */

//SimpleRouter::get('/admin/set-states-and-cities', [ConfigureCitiesAndStates::class, 'persistCitiesAndStates']);


SimpleRouter::post('/user', [UsersController::class, 'create']);
SimpleRouter::group(['middleware' => AuthMiddleware::class], function () {
    SimpleRouter::get('/login', []);
    SimpleRouter::get('/users', [UsersController::class, 'showAll']);
    SimpleRouter::get('/user/{id}', [UsersController::class, 'show']);
    SimpleRouter::delete('/user/{id}', [UsersController::class, 'delete']);
    SimpleRouter::post('/user/{id}', [UsersController::class, 'update']);
    SimpleRouter::group(['prefix' => '/natural-person'], function () {
        SimpleRouter::post('/create', [AccountController::class, 'createNaturalPersonAccount']);
    });
    SimpleRouter::group(['prefix' => '/legal-person'], function () {
        SimpleRouter::post('/create', [AccountController::class, 'createLegalPersonAccount']);
    });
    SimpleRouter::group(['prefix' => '/transactions'], function () {
        SimpleRouter::post('/deposit', [TransactionsController::class, 'deposit']);
        SimpleRouter::post('/withdraw', [TransactionsController::class, 'withdraw']);
        SimpleRouter::post('/transfer', [TransactionsController::class, 'transfer']);
    });
});
