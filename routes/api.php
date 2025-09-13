<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CabangController;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\ManageAdminCabangController;
use App\Http\Controllers\ProdukController;

// Public routes for authentication
Route::post('/super-admin/login', [AuthController::class, 'loginSuperAdmin']);
Route::post('/admin-cabang/login', [AuthController::class, 'loginAdminCabang']);

// Routes protected by Sanctum middleware
Route::middleware('auth:sanctum')->group(function () {
    // Current authenticated user
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Logout route
    Route::post('/logout', [AuthController::class, 'logout']);

    // Routes that can only be accessed by Super Admin
    Route::middleware('role:super admin')->group(function () {
        // Cabang Management API
        Route::get('/cabang', [CabangController::class, 'index']);
        Route::post('/cabang', [CabangController::class, 'store']);
        Route::put('/cabang/{id_cabang}', [CabangController::class, 'update']);
        Route::delete('/cabang/{id_cabang}', [CabangController::class, 'destroy']);

        // Admin Cabang Management API
        Route::get('/cabang-without-admin', [ManageAdminCabangController::class, 'getCabangWithoutAdmin']);
        Route::post('/create-admin-cabang', [ManageAdminCabangController::class, 'createAdminCabang']);

        // Produk Management API
        Route::get('/produk', [ProdukController::class, 'index']);
        Route::post('/produk', [ProdukController::class, 'store']);
        Route::put('/produk/{id_produk}', [ProdukController::class, 'update']);
        Route::delete('/produk/{id_produk}', [ProdukController::class, 'destroy']);

        // Karyawan Management API
        Route::get('/karyawan', [KaryawanController::class, 'index']);
        Route::post('/karyawan', [KaryawanController::class, 'store']);
        Route::put('/karyawan/{id_karyawan}', [KaryawanController::class, 'update']);
        Route::delete('/karyawan/{id_karyawan}', [KaryawanController::class, 'destroy']);
    });
});
