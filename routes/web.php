<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CabangController;
use App\Http\Controllers\ManageAdminCabangController;

Route::post('/super-admin/login', [AuthController::class, 'loginSuperAdmin']);
Route::post('/admin-cabang/login', [AuthController::class, 'loginAdminCabang']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


// Group route yang hanya bisa diakses oleh Super Admin
Route::middleware(['auth:sanctum', 'role:superadmin'])->group(function () {
    Route::get('/cabang-without-admin', [ManageAdminCabangController::class, 'getCabangWithoutAdmin']);
    Route::post('/create-admin-cabang', [ManageAdminCabangController::class, 'createAdminCabang']);
});

Route::middleware(['auth:sanctum', 'role:superadmin'])->group(function () {
    Route::get('/cabang', [CabangController::class, 'index']);
    Route::post('/cabang', [CabangController::class, 'store']);
    Route::put('/cabang/{id_cabang}', [CabangController::class, 'update']);
    Route::delete('/cabang/{id_cabang}', [CabangController::class, 'destroy']);
});
