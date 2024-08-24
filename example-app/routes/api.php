<?php

use App\Http\Controllers\Auth\ApiAuthController;
use App\Http\Controllers\VerificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::group(['prefix' => 'auth'], function () {
    Route::post('login', [ApiAuthController::class, 'login']);

    Route::group(['middleware' => 'auth:api'], function () {
        Route::post('/verify', [VerificationController::class, 'verify']);
    });
});
