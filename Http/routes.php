<?php

Route::group(['middleware' => 'auth:api'], function () {
    Route::apiResource('users', '\Modules\User\Http\Controllers\UserController');
});

Route::post('auth/login', '\Modules\User\Http\Controllers\AuthController@login');
