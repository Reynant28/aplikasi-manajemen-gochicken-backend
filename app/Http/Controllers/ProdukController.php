<?php

namespace App\Http\Controllers;

use App\Models\ProdukModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProdukController extends Controller
{
    /**
     * Menampilkan semua data produk.
     */
    public function index()
    {
        $produk = ProdukModel::all();

        return response()->json([
            'status' => 'success',
            'data' => $produk,
        ]);
    }

    /**
     * Tambah produk baru.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_produk' => 'required|string|unique:produk,id_produk',
            'nama_produk' => 'required|string|max:255',
            'deskripsi' => 'required|string|max:255',
            'harga' => 'required|numeric',
            'id_stock_cabang' => 'required|integer',
            'gambar_produk' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $produk = ProdukModel::create([
            'id_produk' => $request->id_produk,
            'nama_produk' => $request->nama_produk,
            'deskripsi' => $request->deskripsi,
            'harga' => $request->harga,
            'id_stock_cabang' => $request->id_stock_cabang,
            'gambar_produk' => $request->gambar_produk,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Produk berhasil ditambahkan.',
            'data' => $produk,
        ], 201);
    }

    /**
     * Edit data produk.
     */
    public function update(Request $request, $id_produk)
    {
        $produk = ProdukModel::find($id_produk);

        if (!$produk) {
            return response()->json([
                'status' => 'error',
                'message' => 'Produk tidak ditemukan.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama_produk' => 'sometimes|required|string|max:255',
            'deskripsi' => 'sometimes|required|string|max:255',
            'harga' => 'sometimes|required|numeric',
            'id_stock_cabang' => 'sometimes|required|integer',
            'gambar_produk' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $produk->nama_produk = $request->nama_produk ?? $produk->nama_produk;
        $produk->deskripsi = $request->deskripsi ?? $produk->deskripsi;
        $produk->harga = $request->harga ?? $produk->harga;
        $produk->id_stock_cabang = $request->id_stock_cabang ?? $produk->id_stock_cabang;
        if ($request->hasFile('gambar_produk')) {
            $produk->gambar_produk = $request->file('gambar_produk')->store('produk_images', 'public');
        }

        $produk->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Produk berhasil diupdate.',
            'data' => $produk,
        ]);
    }

    /**
     * Hapus produk.
     */
    public function destroy($id_produk)
    {
        $produk = ProdukModel::find($id_produk);

        if (!$produk) {
            return response()->json([
                'status' => 'error',
                'message' => 'Produk tidak ditemukan.',
            ], 404);
        }

        $produk->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Produk berhasil dihapus.',
        ], 200);
    }
}
