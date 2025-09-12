<?php

namespace App\Http\Controllers;

use App\Models\CabangModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CabangController extends Controller
{
    /**
     * Menampilkan semua data cabang.
     */
    public function index()
    {
        $cabang = CabangModel::all();

        return response()->json([
            'status' => 'success',
            'data' => $cabang,
        ]);
    }

    /**
     * Tambah cabang baru.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_cabang' => 'required|string|unique:cabang,id_cabang',
            'nama_cabang' => 'required|string|max:255',
            'alamat' => 'required|string|max:255',
            'telepon' => 'required|string|max:20',
            'password_cabang' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $cabang = CabangModel::create([
            'id_cabang' => $request->id_cabang,
            'nama_cabang' => $request->nama_cabang,
            'alamat' => $request->alamat,
            'telepon' => $request->telepon,
            'password_cabang' => Hash::make($request->password_cabang),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Cabang berhasil ditambahkan.',
            'data' => $cabang,
        ], 201);
    }

    /**
     * Edit data cabang.
     */
    public function update(Request $request, $id_cabang)
    {
        $cabang = CabangModel::find($id_cabang);

        if (!$cabang) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cabang tidak ditemukan.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama_cabang' => 'sometimes|required|string|max:255',
            'alamat' => 'sometimes|required|string|max:255',
            'telepon' => 'sometimes|required|string|max:20',
            'password_cabang' => 'nullable|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $cabang->nama_cabang = $request->nama_cabang ?? $cabang->nama_cabang;
        $cabang->alamat = $request->alamat ?? $cabang->alamat;
        $cabang->telepon = $request->telepon ?? $cabang->telepon;

        // Hash password jika ada perubahan
        if ($request->filled('password_cabang')) {
            $cabang->password_cabang = Hash::make($request->password_cabang);
        }

        $cabang->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Cabang berhasil diupdate.',
            'data' => $cabang,
        ]);
    }

    /**
     * Hapus cabang.
     */
    public function destroy($id_cabang)
    {
        $cabang = CabangModel::find($id_cabang);

        if (!$cabang) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cabang tidak ditemukan.',
            ], 404);
        }

        $cabang->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Cabang berhasil dihapus.',
        ], 200);
    }
}
