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
    Route::get('products/{sku}', 'API\ProductController@show');
});

Route::middleware(['auth:api', 'sessions'])->group(function () {
    Route::get('profile', 'API\UserController@profile');
    Route::get('logout', 'API\UserController@logout');

    Route::get('carts', 'API\CartController@index');
    Route::post('carts', 'API\CartController@store');
    Route::put('carts/{cart_id}', 'API\CartController@update');
    Route::delete('carts/{cart_id}', 'API\CartController@destroy');
    Route::delete('carts', 'API\CartController@clear');

    Route::get('carts/shipping-options', 'API\CartController@shippingOptions');
    Route::post('carts/set-shipping', 'API\CartController@setShipping');
});
