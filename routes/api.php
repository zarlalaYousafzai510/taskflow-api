
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TicketController;

// Public routes - no token required
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected routes - JWT token required
Route::middleware('auth:api')->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::middleware('role:customer,agent')->group(function () {
        Route::get('/tickets', [TicketController::class, 'index']);
        Route::get('/tickets/{id}', [TicketController::class, 'show']);
    });

    Route::middleware('role:customer')->post('/tickets', [TicketController::class, 'store']);
    Route::middleware('role:agent')->put('/tickets/{id}', [TicketController::class, 'update']);
    Route::middleware('role:agent')->post('/tickets/{id}/reply', [TicketController::class, 'reply']);
    Route::middleware('role:agent')->delete('/tickets/{id}', [TicketController::class, 'destroy']);

});
