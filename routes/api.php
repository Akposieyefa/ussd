<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'api', 'prefix' => 'v1'], function () {

    Route::prefix('ussd')->group(function () { 
        Route::controller(App\Http\Controllers\Api\UssdController::class)->group(function () {
            Route::post('', 'handleUssd');
        });
    });
    
    Route::prefix('ussd-test')->group(function () {
        Route::post('', [App\Http\Controllers\Api\UssdController::class, 'testUssd']);
    });

});