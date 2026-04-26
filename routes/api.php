<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\SwadayaController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\EarningsController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\FoodController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Location Proxy (Tanpa Auth untuk Search/Reverse awal)
Route::get('/location/search', [LocationController::class, 'search']);
Route::get('/location/reverse', [LocationController::class, 'reverse']);

Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);

    // User Profile (Penumpang)
    Route::get('/user/profile', [UserController::class, 'me']);
    Route::put('/user/profile', [UserController::class, 'updateProfile']);
    Route::post('/user/profile/photo', [UserController::class, 'uploadPhoto']);

    // Driver Profile & Status
    Route::get('/drivers/me', [DriverController::class, 'me']);           // Flutter butuh ini
    Route::post('/drivers/upload-photo', [DriverController::class, 'uploadProfileImage']);  // BARU
    Route::put('/drivers/profile', [DriverController::class, 'updateProfile']);  // BARU
    Route::post('/drivers/profile', [DriverController::class, 'updateProfile']);  // Support POST for older client implementations
    Route::post('/driver/online', [DriverController::class, 'updateOnline']);
    Route::post('/driver/location', [DriverController::class, 'updateLocation']);
    Route::post('/driver/auto-bid', [DriverController::class, 'updateAutoBid']);
    Route::post('/drivers/capital', [DriverController::class, 'updateCapital']);
    Route::post('/drivers/services', [DriverController::class, 'updateServices']);

    // Orders
    Route::post('/orders', [OrderController::class, 'store']);                // REST API untuk Customer membuat order baru
    Route::get('/orders/available', [OrderController::class, 'available']);   // Polling di HomeScreen
    Route::post('/orders/accept', [OrderController::class, 'accept']);       // Terima order
    Route::get('/orders/active', [OrderController::class, 'getActiveOrder']);
    Route::get('/orders/history', [OrderController::class, 'history']); // Harus sebelum {id}
    Route::get('/orders/chat-history', [OrderController::class, 'chatHistory']);
    Route::post('/orders/calculate-tariff', [OrderController::class, 'calculateTariff']);
    Route::get('/orders/{id}', [OrderController::class, 'show']); // Polling rincian order (status & driver_lat)
    // Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus']); // Wait, I see this is duplicated or different? 
    Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus']);
    Route::post('/orders/{id}/rating', [OrderController::class, 'submitRating']);

    // Earnings
    Route::get('/drivers/earnings', [EarningsController::class, 'summary']);
    Route::get('/drivers/earnings/transactions', [EarningsController::class, 'transactions']);

    // Live Chat
    Route::get('/chats/{orderId}/messages', [ChatController::class, 'getMessages']);
    Route::post('/chats/{orderId}/messages', [ChatController::class, 'store']);

    // Swadaya
    Route::get('/drivers/{id}/swadaya-programs', [SwadayaController::class, 'index']);
    Route::post('/drivers/{id}/swadaya-programs', [SwadayaController::class, 'join']);
    Route::get('/swadaya-programs/{programId}', [SwadayaController::class, 'show']);

    // GoFood
    Route::get('/food/categories', [FoodController::class, 'categories']);
    Route::get('/food/restaurants', [FoodController::class, 'index']);
    Route::get('/food/restaurants/{id}', [FoodController::class, 'show']);
});

// Admin Routes
Route::prefix('admin')->group(function () {
    Route::get('/stats', [AdminController::class, 'getStats']);
    Route::get('/drivers', [AdminController::class, 'listDrivers']);
    Route::post('/drivers/{id}/verify', [AdminController::class, 'verifyDriver']);
    Route::get('/orders', [AdminController::class, 'listOrders']);
    Route::get('/passengers', [AdminController::class, 'listPassengers']);
    Route::get('/pricing', [AdminController::class, 'getPricing']);
    Route::post('/pricing', [AdminController::class, 'updatePricing']);
    Route::post('/reset-revenue', [AdminController::class, 'resetRevenue']);
});