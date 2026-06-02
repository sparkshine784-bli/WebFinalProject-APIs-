<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GradeController;
use Illuminate\Support\Facades\Route;

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

// Auth routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);

    Route::middleware('auth.jwt')->group(function () {
        Route::post('/logout',  [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::get('/me',       [AuthController::class, 'me']);
    });
});

// Grades routes
Route::prefix('grades')->middleware('auth.jwt')->group(function () {
    Route::get('/',       [GradeController::class, 'index'])->middleware('role:student');
    Route::post('/',      [GradeController::class, 'store'])->middleware('role:admin,teacher');
    Route::put('/{id}',   [GradeController::class, 'update'])->middleware('role:admin,teacher');
});

// Attendance routes
Route::prefix('attendance')->middleware('auth.jwt')->group(function () {
    Route::get('/',       [AttendanceController::class, 'index'])->middleware('role:student');
    Route::post('/',      [AttendanceController::class, 'store'])->middleware('role:admin,teacher');
    Route::put('/{id}',   [AttendanceController::class, 'update'])->middleware('role:admin,teacher');
});
