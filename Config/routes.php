<?php

use Pecee\SimpleRouter\SimpleRouter;
use WjCrypto\Controllers\AccountController;
use WjCrypto\Controllers\TransactionsController;
use WjCrypto\Controllers\UsersController;
use WjCrypto\Middlewares\AuthMiddleware;

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
