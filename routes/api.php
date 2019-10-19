<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/login', 'Api\AuthController@login')->name('login');
Route::post('/forgot', 'Api\SendPasswordResetController@forgot')->name('forgot-password');
Route::post('/password-reset', 'Api\ResetPasswordController@resetUserPassword')->name('password-reset');
Route::post('/refresh-token', 'Api\AuthController@refreshToken');

/**
 * Rutas que necesitan autenticación para usar la api
 * seccionada por autorización basada en el rol o roles del usuario.
 */
Route::middleware('jwt.auth')->group(function () {

    Route::middleware('auth.role:superadmin,admin')->group(function () {
        Route::get('/roles', 'Api\UsersController@retrieveRoles');
        Route::post('/create-user', 'Api\UsersController@createUser');
    });

    Route::post('/logout', 'Api\AuthController@logout');
});
