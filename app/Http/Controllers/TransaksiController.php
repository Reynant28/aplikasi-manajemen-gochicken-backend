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
            'id_cabang' => 'required|exists:cabang,id_cabang',
            'nama_pelanggan' => 'required|string|max:255',
            'metode_pembayaran' => 'required|string',
            'status_transaksi' => 'required|in:OnLoan,Selesai',
            'details' => 'required|array|min:1',
            'details.*.id_produk' => 'required|exists:produk,id_produk',
            'details.*.jumlah_produk' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak valid.', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $totalHarga = 0;
            foreach ($request->details as $item) {
                $produk = ProdukModel::find($item['id_produk']);
                if (!$produk) throw new Exception('Produk tidak ditemukan.');
                $totalHarga += $produk->harga * $item['jumlah_produk'];
            }

            $today = Carbon::now();
            $kodeTransaksi = 'TRNSK-' . $today->format('dmY-His');

            // Buat transaksi
            $transaksi = TransaksiModel::create([
                'id_cabang' => $request->id_cabang,
                'nama_pelanggan' => $request->nama_pelanggan,
                'tanggal_waktu' => $today,
                'metode_pembayaran' => $request->metode_pembayaran,
                'status_transaksi' => $request->status_transaksi,
                'total_harga' => $totalHarga,
                'kode_transaksi' => $kodeTransaksi,
            ]);

            // ✨ DEBUG: Cek apakah transaksi berhasil dibuat dan punya ID
            if (!$transaksi || !$transaksi->id_transaksi) {
                throw new Exception('Gagal membuat transaksi atau ID transaksi tidak tersedia.');
            }

            // Log untuk debugging
            Log::info('Transaksi created:', [
                'id' => $transaksi->id_transaksi,
                'kode' => $transaksi->kode_transaksi
            ]);

            foreach ($request->details as $item) {
                $produk = ProdukModel::find($item['id_produk']);

                // ✨ PERBAIKAN: Pastikan id_transaksi tidak null
                $detailData = [
                    'id_transaksi' => $transaksi->id_transaksi, // Pastikan ini tidak null
                    'id_produk' => $item['id_produk'],
                    'jumlah_produk' => $item['jumlah_produk'],
                    'harga_item' => $produk->harga,
                    'subtotal' => $item['jumlah_produk'] * $produk->harga,
                ];

                Log::info('Creating detail transaksi:', $detailData);

                DetailTransaksiModel::create($detailData);

                // Cek stok setelah membuat detail transaksi
                $stok = StokCabangModel::where('id_cabang', $request->id_cabang)
                        ->where('id_produk', $item['id_produk'])
                        ->first();

                if (!$stok || $stok->jumlah_stok < $item['jumlah_produk']) {
                    throw new Exception('Stok untuk produk "' . $produk->nama_produk . '" tidak mencukupi. Stok tersedia: ' . ($stok ? $stok->jumlah_stok : 0));
                }
                $stok->decrement('jumlah_stok', $item['jumlah_produk']);
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Pemesanan berhasil dibuat.',
                'data' => [
                    'id_transaksi' => $transaksi->id_transaksi,
                    'kode_transaksi' => $transaksi->kode_transaksi
                ]
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error creating transaction: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat pesanan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menampilkan detail transaksi tertentu.
     */
    public function show($id_transaksi)
    {
        $transaksi = TransaksiModel::with('detail.produk', 'cabang')->find($id_transaksi);

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
