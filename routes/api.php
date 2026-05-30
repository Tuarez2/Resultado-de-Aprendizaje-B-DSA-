<?php

use App\Http\Controllers\Api\DocentesApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('docentes', DocentesApiController::class);
