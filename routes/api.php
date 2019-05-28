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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

 
Route::get('cities', 'CityController@index');
Route::get('cities/{city}', 'CityController@show');
Route::get('cities/autocomplete/{search}', ['middleware' => 'cors', 'uses' => 'CityController@autocomplete']);

Route::post('cities/cheapest', 'CityController@cheapest');
