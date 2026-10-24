<?php

use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\TokenController;
use App\Http\Middleware\AuthenticateApiToken;
use Illuminate\Support\Facades\Route;

Route::post('/auth/token', [TokenController::class, 'issue'])->middleware('throttle:5,1');

Route::middleware(AuthenticateApiToken::class)->group(function () {
    Route::delete('/auth/token', [TokenController::class, 'revoke']);
    Route::get('/clients', [ContentController::class, 'clients']);
    Route::post('/clients', [ClientController::class, 'store']);
    Route::get('/contents', [ContentController::class, 'contents']);
    Route::get('/contents/{content}', [ContentController::class, 'show']);
    Route::post('/contents', [ContentController::class, 'store']);
    Route::put('/contents/{content}', [ContentController::class, 'update']);
    Route::patch('/contents/{content}', [ContentController::class, 'update']);
    Route::delete('/contents/{content}', [ContentController::class, 'destroy']);
});
