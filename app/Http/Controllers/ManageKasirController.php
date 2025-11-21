<?php

namespace App\Http\Controllers;

use App\Models\UsersModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class ManageKasirController extends Controller
{
    /**
     * Tampilkan daftar kasir yang ada.
     */
    public function listKasir()
    {
        // Get the logged-in user
        $loggedInUser = Auth::user();

        // If user is admin cabang, only show kasir from their branch
        if ($loggedInUser->role === 'admin cabang') {
            $kasir = UsersModel::where('role', 'kasir')
                ->where('id_cabang', $loggedInUser->id_cabang)
                ->with('cabang')
                ->get();
        } else {
            // If super admin, show all kasir
            $kasir = UsersModel::where('role', 'kasir')
                ->with('cabang')
                ->get();
        }

        return response()->json([
            'status' => 'success',
            'data' => $kasir,
        ]);
    }

    /**
     * Buat akun kasir baru.
     */
    public function createKasir(Request $request)
    {
        // Get the logged-in user
        $loggedInUser = Auth::user();

        // Determine id_cabang based on user role
        if ($loggedInUser->role === 'admin cabang') {
            // Auto-assign the admin's branch
            $id_cabang = $loggedInUser->id_cabang;
        } else {
            // For super admin, use the provided id_cabang or throw error if not provided
            $validator = Validator::make($request->all(), [
                'id_cabang' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'ID Cabang harus diisi untuk Super Admin.',
                    'errors' => $validator->errors(),
                ], 422);
            }
            $id_cabang = $request->id_cabang;
        }

        // 1. Validasi data yang masuk
        $validator = Validator::make($request->all(), [
            'nama' => 'required',
            'email' => 'required|email|unique:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // 2. Simpan data user baru dengan password null
        $user = UsersModel::create([
            'nama' => $request->nama,
            'email' => $request->email,
            'password' => null, // Set password to null
            'role' => 'kasir',
            'id_cabang' => $id_cabang, // Auto-assigned based on admin's branch
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Akun kasir berhasil dibuat.',
            'data' => $user,
        ], 201); // 201 Created
    }

    public function updateKasir($id_user, Request $request) {
        // 1. Find the kasir
        $kasir = UsersModel::find($id_user);
        if (!$kasir) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kasir tidak ditemukan.',
            ], 404);
        }

        // Get the logged-in user
        $loggedInUser = Auth::user();

        // Check if admin cabang is trying to update kasir from different branch
        if ($loggedInUser->role === 'admin cabang' && $kasir->id_cabang !== $loggedInUser->id_cabang) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses untuk mengupdate kasir dari cabang lain.',
            ], 403);
        }

        // 2. Validation
        $validator = Validator::make($request->all(), [
            'nama' => 'required',
            'email' => 'required|email|unique:users,email,' . $id_user . ',id_user',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // 3. Update the kasir
        $kasir->nama = $request->nama;
        $kasir->email = $request->email;

        // Only allow super admin to change branch
        if ($loggedInUser->role !== 'admin cabang' && $request->has('id_cabang')) {
            $kasir->id_cabang = $request->id_cabang;
        }

        $kasir->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Kasir berhasil diupdate.',
            'data' => $kasir,
        ], 200);
    }

    public function deleteKasir($id_user) {
        $kasir = UsersModel::find($id_user);

        if (!$kasir) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kasir tidak ditemukan.',
            ], 404);
        }

        // Get the logged-in user
        $loggedInUser = Auth::user();

        // Check if admin cabang is trying to delete kasir from different branch
        if ($loggedInUser->role === 'admin cabang' && $kasir->id_cabang !== $loggedInUser->id_cabang) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses untuk menghapus kasir dari cabang lain.',
            ], 403);
        }

        $kasir->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Kasir berhasil dihapus.',
        ], 200);
    }
}
