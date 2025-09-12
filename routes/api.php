<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CabangController;
use App\Http\Controllers\ManageAdminCabangController;

// Public routes for authentication
Route::post('/super-admin/login', [AuthController::class, 'loginSuperAdmin']);
Route::post('/admin-cabang/login', [AuthController::class, 'loginAdminCabang']);

Route::post('/test', function () {
    return response()->json(['message' => 'API jalan']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (\Illuminate\Http\Request $request) {
        return $request->user();
    });

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware('role:super_admin')->group(function () {
        Route::get('/cabang', [CabangController::class, 'index']);
        Route::post('/cabang', [CabangController::class, 'store']);
        Route::put('/cabang/{id_cabang}', [CabangController::class, 'update']);
        Route::delete('/cabang/{id_cabang}', [CabangController::class, 'destroy']);

        Route::get('/cabang-without-admin', [ManageAdminCabangController::class, 'getCabangWithoutAdmin']);
        Route::post('/create-admin-cabang', [ManageAdminCabangController::class, 'createAdminCabang']);
    });
});
