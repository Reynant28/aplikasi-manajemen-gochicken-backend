<?php

namespace App\Http\Controllers;

use App\Models\UsersModel;
use App\Models\CabangModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ManageAdminCabangController extends Controller
{
    /**
     * Tampilkan daftar cabang yang belum memiliki admin. Dan menampilkan daftar cabang yang sudah memiliki admin.
     * Endpoint ini berguna untuk frontend Super Admin memilih cabang.
     */
    public function getCabangWithoutAdmin()
    {
        $cabang = CabangModel::whereDoesntHave('user', function ($query) {
            $query->where('role', 'admin cabang');
        })->get();

        return response()->json([
            'status' => 'success',
            'data' => $cabang,
        ]);
    }

    /**
     * Buat akun admin cabang baru.
     */
    public function createAdminCabang(Request $request)
    {
        // 1. Validasi data yang masuk
        $validator = Validator::make($request->all(), [
            'id_user' => 'required',
            'nama' => 'required',
            'email' => 'required',
            'password' => 'required',
            'id_cabang' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // 2. Pastikan cabang yang dipilih belum memiliki admin cabang
        $cabang = CabangModel::with('user')->find($request->id_cabang);

        if ($cabang->user->contains('role', 'admin cabang')) {
            throw ValidationException::withMessages([
                'id_cabang' => ['Cabang ini sudah memiliki admin cabang.'],
            ]);
        }

        // 3. Simpan data user baru dengan password yang di-hash
        $user = UsersModel::create([
            'id_user' => $request->id_user,
            'nama' => $request->nama,
            'email' => $request->email,
            'password' => Hash::make($request->password), // Hashing password
            'role' => 'admin cabang',
            'id_cabang' => $request->id_cabang,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Akun admin cabang berhasil dibuat.',
            'data' => $user,
        ]);
    }
}
