<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AssetUnitController;
use App\Http\Controllers\Api\TransactionApiController;
use App\Http\Controllers\Api\CustomerSyncController;
use App\Http\Controllers\Api\StockController;

/*
|--------------------------------------------------------------------------
| API Routes — Asset Management System
|--------------------------------------------------------------------------
|
| Semua endpoint diawali /api/v1/
| Auth: Laravel Sanctum (Bearer token)
|
| Untuk generate token admin:
|   $user = User::find(1);
|   $token = $user->createToken('api-access')->plainTextToken;
|
*/

// Health check publik
// Route::get('/ping', fn() => response()->json(['status' => 'ok', 'system' => 'DWIKI Asset Management']));

// =============================================
// TRIDATU EXTERNAL INTEGRATION
// Endpoints ini bisa diakses oleh sistem eksternal (Tridatu Netmon)
// Menggunakan Header Wajib: X-API-KEY
// =============================================
Route::prefix('v1')->middleware('tridatu_api_key')->group(function () {
    Route::get('/customers',      [CustomerSyncController::class, 'index']);
    Route::post('/customers/sync', [CustomerSyncController::class, 'sync']);
    
    // Proxy ke Tridatu API (Staff data)
    Route::get('/staff', function () {
        $service = app(\App\Services\TridatuNetmonService::class);
        return response()->json([
            'success' => true,
            'data'    => $service->getStaffList(),
        ]);
    });

    Route::get('/staff/{tridatuUserId}', function (string $tridatuUserId) {
        $service = app(\App\Services\TridatuNetmonService::class);
        $staff = $service->getStaffById($tridatuUserId);
        return $staff
            ? response()->json(['success' => true, 'data' => $staff])
            : response()->json(['success' => false, 'message' => 'Staff tidak ditemukan.'], 404);
    });

    // Asset history/monitoring per customer (CIDXXX reference)
    Route::get('/customers/{externalId}/assets', [CustomerSyncController::class, 'assetsByExternalId']);
});

// Semua route di bawah wajib auth via Sanctum (Aplikasi internal / Dashboard)
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {

    // =============================================
    // STOCK & ASSETS (Internal)
    // =============================================
    Route::get('/stock',          [StockController::class, 'index']);
    Route::get('/stock/by-site',  [StockController::class, 'bySite']);

    Route::get('/asset-units',                  [AssetUnitController::class, 'index']);
    Route::get('/asset-units/{assetUnit}',      [AssetUnitController::class, 'show']);
    Route::patch('/asset-units/{assetUnit}/status', [AssetUnitController::class, 'updateStatus']);

    Route::get('/transactions',          [TransactionApiController::class, 'index']);
    Route::post('/transactions',         [TransactionApiController::class, 'store']);
    Route::get('/transactions/{transaction}', [TransactionApiController::class, 'show']);
});
