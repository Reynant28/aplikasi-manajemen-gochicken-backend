<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\BahanBakuPakaiModel;
use App\Models\BahanBakuModel;

class BahanBakuPakaiController extends Controller
{
    /**
     * Tampilkan pemakaian bahan baku harian
     */
    public function index(Request $request)
    {
        $tanggal = $request->query('tanggal', date('Y-m-d'));

        // Gunakan Eloquent dengan relasi - FIX: Use BahanBakuPakaiModel, not BahanBakuModel
        $pemakaian = BahanBakuPakaiModel::with('bahanBaku')
            ->whereDate('tanggal', $tanggal) // This works because tanggal is in bahan_baku_pakai table
            ->get()
            ->map(function ($item) {
                return [
                    'id_pemakaian' => $item->id_pemakaian,
                    'nama_bahan' => $item->bahanBaku->nama_bahan ?? 'N/A',
                    'satuan' => $item->bahanBaku->satuan ?? 'N/A',
                    'harga_satuan' => $item->bahanBaku->harga_satuan ?? 0,
                    'jumlah_pakai' => $item->jumlah_pakai,
                    'total_modal' => ($item->bahanBaku->harga_satuan ?? 0) * $item->jumlah_pakai,
                    'catatan' => $item->catatan,
                    'id_cabang' => $item->id_cabang
                ];
            });

        return response()->json([
            'status' => 'success',
            'tanggal' => $tanggal,
            'data' => $pemakaian
        ]);
    }

    /**
     * Tambah pemakaian bahan baku harian
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tanggal' => 'required|date',
            'id_bahan_baku' => 'required|exists:bahan_baku,id_bahan_baku',
            'jumlah_pakai' => 'required|numeric|min:0.01',
            'catatan' => 'nullable|string',
            'id_cabang' => 'required|exists:cabang,id_cabang'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Ambil bahan baku dengan lock untuk mencegah race condition
            $bahanBaku = BahanBakuModel::where('id_bahan_baku', $request->id_bahan_baku)
                ->lockForUpdate()
                ->first();

            if (!$bahanBaku) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Bahan baku tidak ditemukan.'
                ], 404);
            }

            $stokSkrg = (float) $bahanBaku->jumlah_stok;
            $pakai = (float) $request->jumlah_pakai;

            if ($stokSkrg < $pakai) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Stok bahan baku tidak mencukupi. Stok saat ini: ' . $stokSkrg . ' ' . $bahanBaku->satuan,
                ], 400);
            }

            // Kurangi stok bahan baku
            $bahanBaku->decrement('jumlah_stok', $pakai);

            // Generate ID pemakaian
            $id_pemakaian = 'PB_' . date('YmdHis') . '_' . rand(100, 999);

            // Simpan pemakaian bahan baku
            BahanBakuPakaiModel::create([
                'id_pemakaian' => $id_pemakaian,
                'tanggal' => $request->tanggal,
                'id_bahan_baku' => $request->id_bahan_baku,
                'jumlah_pakai' => $request->jumlah_pakai,
                'catatan' => $request->catatan,
                'id_cabang' => $request->id_cabang,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Pemakaian bahan baku berhasil disimpan dan stok diperbarui.',
                'data' => [
                    'id_pemakaian' => $id_pemakaian
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menyimpan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update data pemakaian bahan baku
     */
    public function update(Request $request, $id_pemakaian)
    {
        $validator = Validator::make($request->all(), [
            'tanggal' => 'required|date',
            'id_bahan_baku' => 'required|exists:bahan_baku,id_bahan_baku',
            'jumlah_pakai' => 'required|numeric|min:0.01',
            'catatan' => 'nullable|string',
            'id_cabang' => 'required|exists:cabang,id_cabang'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            $pemakaian = BahanBakuPakaiModel::where('id_pemakaian', $id_pemakaian)->first();

            if (!$pemakaian) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data pemakaian tidak ditemukan.'
                ], 404);
            }

            // Kembalikan stok lama
            $bahanBakuLama = BahanBakuModel::where('id_bahan_baku', $pemakaian->id_bahan_baku)->first();
            if ($bahanBakuLama) {
                $bahanBakuLama->increment('jumlah_stok', $pemakaian->jumlah_pakai);
            }

            // Cek stok baru
            $bahanBakuBaru = BahanBakuModel::where('id_bahan_baku', $request->id_bahan_baku)
                ->lockForUpdate()
                ->first();

            if (!$bahanBakuBaru) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Bahan baku tidak ditemukan.'
                ], 404);
            }

            $stokSkrg = (float) $bahanBakuBaru->jumlah_stok;
            $pakai = (float) $request->jumlah_pakai;

            if ($stokSkrg < $pakai) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Stok bahan baku tidak mencukupi. Stok saat ini: ' . $stokSkrg . ' ' . $bahanBakuBaru->satuan,
                ], 400);
            }

            // Kurangi stok baru
            $bahanBakuBaru->decrement('jumlah_stok', $pakai);

            // Update pemakaian
            $pemakaian->update([
                'tanggal' => $request->tanggal,
                'id_bahan_baku' => $request->id_bahan_baku,
                'jumlah_pakai' => $request->jumlah_pakai,
                'catatan' => $request->catatan,
                'id_cabang' => $request->id_cabang,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Pemakaian bahan baku berhasil diupdate.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengupdate: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Hapus data pemakaian bahan baku
     */
    public function destroy($id_pemakaian)
    {
        DB::beginTransaction();
        try {
            $pemakaian = BahanBakuPakaiModel::where('id_pemakaian', $id_pemakaian)->first();

            if (!$pemakaian) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data pemakaian tidak ditemukan'
                ], 404);
            }

            // Kembalikan stok
            $bahanBaku = BahanBakuModel::where('id_bahan_baku', $pemakaian->id_bahan_baku)->first();
            if ($bahanBaku) {
                $bahanBaku->increment('jumlah_stok', $pemakaian->jumlah_pakai);
            }

            // Hapus record pemakaian
            $pemakaian->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Pemakaian berhasil dihapus dan stok dikembalikan.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus: ' . $e->getMessage()
            ], 500);
        }
    }
}
