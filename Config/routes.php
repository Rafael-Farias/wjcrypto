<?php

use Pecee\SimpleRouter\SimpleRouter;
use WjCrypto\Controllers\AccountController;
use WjCrypto\Controllers\UsersController;
use WjCrypto\middlewares\AuthMiddleware;

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
});
