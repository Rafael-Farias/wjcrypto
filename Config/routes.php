<?php

use Pecee\SimpleRouter\SimpleRouter;
use WjCrypto\Controllers\UsersController;

SimpleRouter::post('/user', [UsersController::class, 'create']);
SimpleRouter::get('/users', [UsersController::class, 'showUsers']);
SimpleRouter::get('/user/{id}', [UsersController::class, 'showUser']);