<?php

namespace App\Http\Controllers;

use Exception;
use Carbon\Carbon;
use App\Models\ProdukModel;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\TransaksiModel;
use App\Models\StokCabangModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\DetailTransaksiModel;
use Illuminate\Support\Facades\Validator;


class TransaksiController extends Controller
{
    /**
     * Menampilkan semua transaksi dengan detailnya.
     */
    public function index()
    {
        $transaksi = TransaksiModel::with('details.produk', 'cabang')->get();

        return response()->json([
            'status' => 'success',
            'data' => $transaksi,
        ]);
    }

    /**
     * Simpan transaksi baru dari Android app.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kode_transaksi' => 'required|string',
            'id_cabang' => 'required|exists:cabang,id_cabang',
            'nama_pelanggan' => 'required|string|max:255',
            'metode_pembayaran' => 'required|string',
            'status_pembayaran' => 'required|in:OnLoan,Selesai', // Changed from status_transaksi
            'total_harga' => 'required|numeric',
            'items' => 'required|array|min:1', // Changed from details
            'items.*.id_produk' => 'required|exists:produk,id_produk',
            'items.*.jumlah_produk' => 'required|integer|min:1',
            'items.*.harga_item' => 'required|numeric',
            'items.*.subtotal' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak valid.',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Use the kode_transaksi from Android instead of generating new one
            $transaksi = TransaksiModel::create([
                'kode_transaksi' => $request->kode_transaksi,
                'id_cabang' => $request->id_cabang,
                'nama_pelanggan' => $request->nama_pelanggan,
                'tanggal_waktu' => $request->tanggal_waktu ?? now(), // Use provided or current time
                'metode_pembayaran' => $request->metode_pembayaran,
                'status_transaksi' => $request->status_pembayaran, // Map to your database field
                'total_harga' => $request->total_harga,
            ]);

            foreach ($request->items as $item) {
                DetailTransaksiModel::create([
                    'id_transaksi' => $transaksi->id_transaksi,
                    'id_produk' => $item['id_produk'],
                    'jumlah_produk' => $item['jumlah_produk'],
                    'harga_item' => $item['harga_item'],
                    'subtotal' => $item['subtotal'],
                ]);

                // Update stock
                $stok = StokCabangModel::where('id_cabang', $request->id_cabang)
                        ->where('id_produk', $item['id_produk'])
                        ->first();

                if (!$stok || $stok->jumlah_stok < $item['jumlah_produk']) {
                    throw new Exception('Stok tidak mencukupi untuk produk ID: ' . $item['id_produk']);
                }
                $stok->decrement('jumlah_stok', $item['jumlah_produk']);
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Transaksi berhasil dibuat.',
                'data' => $transaksi
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Transaction error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Gagal membuat transaksi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menampilkan detail transaksi tertentu.
     */
    public function show($id_transaksi)
    {
        $transaksi = TransaksiModel::with('details.produk', 'cabang')->find($id_transaksi);

        if (!$transaksi) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Transaksi tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $transaksi,
        ]);
    }

    /**
     * Hapus transaksi beserta detailnya.
     */
    public function destroy($id_transaksi)
    {
        $transaksi = TransaksiModel::find($id_transaksi);

        if (!$transaksi) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Transaksi tidak ditemukan.',
            ], 404);
        }

        DB::beginTransaction();
        try {
            DetailTransaksiModel::where('id_transaksi', $id_transaksi)->delete();
            $transaksi->delete();

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Transaksi berhasil dihapus.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal menghapus transaksi.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function getTransaksiByCabang($id_cabang)
    {
        $transaksi = TransaksiModel::where('id_cabang', $id_cabang)
            ->orderBy('tanggal_waktu', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $transaksi,
        ]);
    }
}
