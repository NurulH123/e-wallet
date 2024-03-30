<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/otp/{phone}/verify', [AuthController::class, 'verifyOtp']);

Route::group(
    ['middleware' => ['auth']],
    function() {
        Route::get('/me', [HomeController::class, 'profile']);
        Route::post('/search/{phone}', [HomeController::class, 'search']);
        Route::post('/activity', [ActivityController::class, 'sendToUser']);
        Route::post('/activity/topup', [ActivityController::class, 'topUp']);
        Route::get('/list-activity', [ActivityController::class,  'activities']);
    }
);