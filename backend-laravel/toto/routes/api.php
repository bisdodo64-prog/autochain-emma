<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\MaintenanceController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\AlertController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AuditController;
use App\Http\Controllers\Api\BlockchainController;

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/web3-login', [AuthController::class, 'web3Login']);

// Statut blockchain public (lecture seule, pas de donnees sensibles)
Route::get('/blockchain/status', [BlockchainController::class, 'status']);
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'app' => 'AutoChain Emma+',
        'time' => now()->toIso8601String(),
    ]);
});
// Avatar public (img src ne peut pas envoyer le Bearer token)
Route::get('/users/{id}/avatar', [AuthController::class, 'avatar']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::post('/auth/avatar', [AuthController::class, 'uploadAvatar']);
    Route::put('/auth/password', [AuthController::class, 'updatePassword']);

    // Blockchain (lecture) — tout utilisateur authentifie via l'API
    Route::get('/blockchain/transactions', [BlockchainController::class, 'transactions']);
    Route::get('/blockchain/stats', [BlockchainController::class, 'stats']);
});

Route::middleware(['auth:sanctum', 'verify.web3'])->group(function () {
    // Vehicles
    Route::get('/vehicles', [VehicleController::class, 'index']);
    Route::post('/vehicles', [VehicleController::class, 'store']);
    Route::get('/vehicles/{id}', [VehicleController::class, 'show']);
    Route::delete('/vehicles/{id}', [VehicleController::class, 'destroy']);
    Route::post('/vehicles/{id}/certify', [VehicleController::class, 'certify']);
    Route::put('/vehicles/{id}/mileage', [VehicleController::class, 'updateMileage']);
    Route::post('/vehicles/{id}/assign-driver', [VehicleController::class, 'assignDriver']);
    Route::get('/vehicles/{id}/timeline', [VehicleController::class, 'getTimeline']);
    Route::get('/vehicles/{id}/documents/{docId}/verify', [VehicleController::class, 'verifyDocument']);

    // Maintenance
    Route::post('/vehicles/{vehicleId}/maintenance', [MaintenanceController::class, 'store']);
    Route::get('/vehicles/{vehicleId}/maintenance', [MaintenanceController::class, 'index']);
    Route::post('/maintenance/{id}/certify', [MaintenanceController::class, 'certify']);

    // Drivers
    Route::get('/drivers', [DriverController::class, 'index']);
    Route::post('/drivers', [DriverController::class, 'store']);
    Route::post('/drivers/{id}/wallet', [DriverController::class, 'assignWallet']);

    // Documents
    Route::post('/vehicles/{vehicleId}/documents', [DocumentController::class, 'upload']);
    Route::get('/vehicles/{vehicleId}/documents', [DocumentController::class, 'index']);
    Route::get('/documents/{id}', [DocumentController::class, 'show']);
    Route::get('/documents/{id}/download', [DocumentController::class, 'download']);
    Route::post('/documents/{id}/certify', [DocumentController::class, 'certify']);
    Route::delete('/documents/{id}', [DocumentController::class, 'destroy']);

    // Health (authenticated light check)
    Route::get('/health/auth', function () {
        return response()->json([
            'status' => 'ok',
            'auth' => true,
            'time' => now()->toIso8601String(),
        ]);
    });

    // Alerts
    Route::get('/alerts', [AlertController::class, 'index']);
    Route::post('/alerts/{id}/dismiss', [AlertController::class, 'dismiss']);
    Route::get('/alerts/stats', [AlertController::class, 'stats']);

    // Audit
    Route::get('/audit/verify', [AuditController::class, 'verify']);

    // Admin users
    Route::middleware(['role:super_admin'])->prefix('admin')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);
    });

    // Blockchain Admin
    Route::middleware(['role:super_admin'])->group(function () {
        Route::post('/blockchain/authorize-garage', [VehicleController::class, 'authorizeGarage']);
        Route::post('/blockchain/sync', [VehicleController::class, 'syncAll']);
    });
});