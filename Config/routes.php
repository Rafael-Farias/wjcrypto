<?php

use Pecee\SimpleRouter\SimpleRouter;
use WjCrypto\Controllers\UsersController;
use WjCrypto\middlewares\AuthMiddleware;

SimpleRouter::group(['middleware' => AuthMiddleware::class], function () {
    SimpleRouter::post('/login', []);
    SimpleRouter::post('/user', [UsersController::class, 'create']);
    SimpleRouter::get('/users', [UsersController::class, 'showAll']);
    SimpleRouter::get('/user/{id}', [UsersController::class, 'show']);
    SimpleRouter::delete('/user/{id}', [UsersController::class, 'delete']);
    SimpleRouter::post('/user/{id}', [UsersController::class, 'update']);
});
