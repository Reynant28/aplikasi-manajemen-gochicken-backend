<?php

namespace App\Http\Controllers;

use App\Models\CabangModel;
use App\Models\UsersModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function loginSuperAdmin(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'password' => 'required',
        ]);

        $user = UsersModel::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['email atau password salah.'],
            ]);
        }

        // Pastikan role-nya adalah 'super admin'
        if ($user->role !== 'super admin') {
             throw ValidationException::withMessages([
                'email' => ['email atau password salah.'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function loginAdminCabang(Request $request)
    {
        $request->validate([
            'id_cabang' => 'required',
            'password_cabang' => 'required',
            'password_pribadi' => 'required',
        ]);

        // Cari cabang berdasarkan id_cabang
        $cabang = CabangModel::where('id_cabang', $request->id_cabang)->first();

        // Cek password cabang
        if (!$cabang || !Hash::check($request->password_cabang, $cabang->password_cabang)) {
            throw ValidationException::withMessages([
                'password_cabang' => ['Password cabang salah.'],
            ]);
        }

        // Cari user (admin cabang) di dalam cabang tersebut
        $user = UsersModel::where('id_cabang', $request->id_cabang)
                            ->where('role', 'admin cabang')
                            ->first();

        // Cek password pribadi (password user)
        if (!$user || !Hash::check($request->password_pribadi, $user->password)) {
            throw ValidationException::withMessages([
                'password_pribadi' => ['Password pribadi salah.'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'user' => $user,
            'token' => $token,
        ]);
    }
}
