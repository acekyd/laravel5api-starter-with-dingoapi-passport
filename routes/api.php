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

$api = app('Dingo\Api\Routing\Router');

$api->version('v1', function ($api) {
	//Unauthenticated routes.
	$api->get('/users', function (Request $request) { return \App\User::all(); });
});
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
