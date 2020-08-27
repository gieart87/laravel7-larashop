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

Route::post('register', 'API\UserController@register');
Route::post('login', 'API\UserController@login');

Route::middleware('client')->group(function () {
    Route::get('products', 'API\ProductController@index');
});

Route::middleware('auth:api')->group(function () {
    Route::get('profile', 'API\UserController@profile');
    Route::get('logout', 'API\UserController@logout');
});
