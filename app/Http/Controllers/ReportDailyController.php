<?php
namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ReportDailyController extends Controller
{
    public function getDailyReport(Request $request)
    {
        try {
            $tanggal = $request->query('tanggal', date('Y-m-d'));

            // Pastikan format tanggal valid
            $date = Carbon::parse($tanggal)->format('Y-m-d');

            // === DEBUG: CEK STATUS TRANSAKSI YANG ADA ===
            $status_check = DB::table('transaksi')
                ->select('status_transaksi', DB::raw('COUNT(*) as count'))
                ->whereRaw('DATE(tanggal_waktu) = ?', [$date])
                ->groupBy('status_transaksi')
                ->get();

            // === DEBUG: CEK DETAIL TRANSAKSI ===
            $detail_check = DB::table('detail_transaksi')
                ->join('transaksi', 'detail_transaksi.id_transaksi', '=', 'transaksi.id_transaksi')
                ->whereRaw('DATE(transaksi.tanggal_waktu) = ?', [$date])
                ->count();

            // === PENJUALAN ===
            // Coba dengan case insensitive
            $penjualan = DB::table('detail_transaksi')
                ->join('transaksi', 'detail_transaksi.id_transaksi', '=', 'transaksi.id_transaksi')
                ->join('produk', 'detail_transaksi.id_produk', '=', 'produk.id_produk')
                ->select(
                    'produk.nama_produk as produk',
                    DB::raw('SUM(detail_transaksi.jumlah_produk) as jumlah_produk'),
                    DB::raw('AVG(detail_transaksi.harga_item) as harga_item'),
                    DB::raw('SUM(detail_transaksi.jumlah_produk * detail_transaksi.harga_item) as total_penjualan_produk')
                )
                ->whereRaw('DATE(transaksi.tanggal_waktu) = ?', [$date])
                ->whereRaw('LOWER(TRIM(transaksi.status_transaksi)) = ?', ['selesai'])
                ->groupBy('produk.nama_produk', 'produk.id_produk')
                ->get();

            $total_penjualan = $penjualan->sum('total_penjualan_produk');

            // === BAHAN BAKU ===
            $bahan_baku = DB::table('bahan_baku_harian')
                ->join('bahan_baku', 'bahan_baku_harian.id_bahan_baku', '=', 'bahan_baku.id_bahan_baku')
                ->select(
                    'bahan_baku.nama_bahan',
                    'bahan_baku.satuan',
                    'bahan_baku.harga_satuan',
                    'bahan_baku_harian.jumlah_pakai',
                    DB::raw('(bahan_baku.harga_satuan * bahan_baku_harian.jumlah_pakai) as modal_produk')
                )
                ->whereRaw('DATE(bahan_baku_harian.tanggal) = ?', [$date])
                ->get();

            $total_modal = $bahan_baku->sum('modal_produk');

            // === PENGELUARAN ===
            $non_cicilan = DB::table('pengeluaran')
                ->join('jenis_pengeluaran', 'pengeluaran.id_jenis', '=', 'jenis_pengeluaran.id_jenis')
                ->select(
                    'pengeluaran.id_pengeluaran',
                    'pengeluaran.tanggal',
                    'pengeluaran.jumlah',
                    'pengeluaran.cicilan_harian',
                    'jenis_pengeluaran.jenis_pengeluaran as jenis',
                    'pengeluaran.keterangan'
                )
                ->where('pengeluaran.cicilan_harian', 0)
                ->whereDate('pengeluaran.tanggal', $date)
                ->get();

            $cicilan = DB::table('pengeluaran')
                ->join('jenis_pengeluaran', 'pengeluaran.id_jenis', '=', 'jenis_pengeluaran.id_jenis')
                ->select(
                    'pengeluaran.id_pengeluaran',
                    'pengeluaran.tanggal',
                    'pengeluaran.jumlah',
                    'pengeluaran.cicilan_harian',
                    'jenis_pengeluaran.jenis_pengeluaran as jenis',
                    'pengeluaran.keterangan'
                )
                ->where('pengeluaran.cicilan_harian', '>', 0)
                ->whereDate('pengeluaran.tanggal', '<=', $date)
                ->whereMonth('pengeluaran.tanggal', Carbon::parse($date)->month)
                ->whereYear('pengeluaran.tanggal', Carbon::parse($date)->year)
                ->get();

            $pengeluaran = $non_cicilan->merge($cicilan);


            $pengeluaran_harian = 0;
            $total_non_installment = 0;
            $pengeluaran_detail = [];

            foreach ($pengeluaran as $item) {
                $tanggalMulai = Carbon::parse($item->tanggal);
                $tanggalSekarang = Carbon::parse($date);
                $is_installment = $item->cicilan_harian > 0;

                if ($is_installment) {
                    $daysInMonth = $tanggalMulai->daysInMonth;
                    $tanggalAkhir = $tanggalMulai->copy()->addDays($daysInMonth - 1);

                    if ($tanggalSekarang->between($tanggalMulai, $tanggalAkhir)) {
                        $harian = $item->cicilan_harian;
                    } else {
                        $harian = 0;
                    }
                } else {
                    $harian = $item->jumlah;
                    $total_non_installment += $item->jumlah;
                }

                $pengeluaran_harian += $harian;

                $pengeluaran_detail[] = [
                    'id_pengeluaran' => $item->id_pengeluaran,
                    'tanggal' => $item->tanggal,
                    'jumlah' => $item->jumlah,
                    'jenis' => $item->jenis,
                    'keterangan' => $item->keterangan,
                    'cicilan_harian' => $harian,
                    'is_installment' => $is_installment,
                    'is_today_expense' => $tanggalSekarang->isSameDay($tanggalMulai),
                ];
            }

            // === ONLOAN ===
            $onloan = DB::table('transaksi')
                ->whereRaw('DATE(tanggal_waktu) = ?', [$date])
                ->whereRaw('LOWER(TRIM(status_transaksi)) = ?', ['onloan'])
                ->sum('total_harga');

            // === LABA & NETT ===
            $laba_harian = $total_penjualan - $total_modal;
            $nett_income = $laba_harian - $pengeluaran_harian;

            // === WARNING ===
            $peringatan = null;
            if (($total_penjualan + $onloan) < ($total_modal + $pengeluaran_harian)) {
                $peringatan = "⚠️ Pendapatan hari ini lebih kecil dari total pengeluaran dan modal";
            }

            // === DEBUG INFO (hapus setelah testing) ===
            $debug = [
                'query_date' => $date,
                'penjualan_count' => $penjualan->count(),
                'total_transaksi' => DB::table('transaksi')
                    ->whereRaw('DATE(tanggal_waktu) = ?', [$date])
                    ->count(),
                'status_breakdown' => $status_check,
                'detail_transaksi_count' => $detail_check,
                'sample_transaksi' => DB::table('transaksi')
                    ->select('id_transaksi', 'status_transaksi', 'tanggal_waktu', 'total_harga')
                    ->whereRaw('DATE(tanggal_waktu) = ?', [$date])
                    ->limit(3)
                    ->get(),
                'raw_sql_penjualan' => DB::table('detail_transaksi')
                    ->join('transaksi', 'detail_transaksi.id_transaksi', '=', 'transaksi.id_transaksi')
                    ->join('produk', 'detail_transaksi.id_produk', '=', 'produk.id_produk')
                    ->whereRaw('DATE(transaksi.tanggal_waktu) = ?', [$date])
                    ->whereRaw('LOWER(TRIM(transaksi.status_transaksi)) = ?', ['selesai'])
                    ->toSql(),
            ];

            return response()->json([
                'tanggal' => $date,
                'penjualan' => [
                    'detail' => $penjualan,
                    'total_penjualan' => $total_penjualan
                ],
                'bahan_baku' => [
                    'detail' => $bahan_baku,
                    'total_modal_bahan_baku' => $total_modal
                ],
                'pengeluaran' => [
                    'detail' => $pengeluaran,
                    'cicilan_harian' => $pengeluaran_harian,
                    'total_non_installment' => $total_non_installment
                ],
                'onloan' => $onloan,
                'penjualan_harian' => $total_penjualan,
                'modal_bahan_baku' => $total_modal,
                'pengeluaran_harian' => $pengeluaran_harian,
                'laba_harian' => $laba_harian,
                'nett_income' => $nett_income,
                'peringatan' => $peringatan,
                'debug' => $debug // Hapus setelah testing
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Terjadi kesalahan saat mengambil data',
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
        }
    }

    // ✅ UPDATE STATUS ORDER dari halaman laporan
    public function updateOrderStatus(Request $request, $id)
    {
        try {
            $status = $request->input('status_transaksi');

            $updated = DB::table('transaksi')
                ->where('id_transaksi', $id)
                ->update([
                    'status_transaksi' => $status,
                    'updated_at' => now()
                ]);

            if ($updated) {
                return response()->json([
                    'success' => true,
                    'message' => 'Status order berhasil diperbarui'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getDailyReportBranch(Request $request)
    {
        try {
            // Ambil ID cabang dari user yang login
            $user = Auth::user();
            $id_cabang = $user->id_cabang;

            // Validasi jika admin tidak memiliki cabang
            if (!$id_cabang) {
                return response()->json([
                    'error' => 'Akses ditolak',
                    'message' => 'Admin tidak terasosiasi dengan cabang manapun'
                ], 403);
            }

            $tanggal = $request->query('tanggal', date('Y-m-d'));
            $date = Carbon::parse($tanggal)->format('Y-m-d');

            // === DEBUG: CEK STATUS TRANSAKSI YANG ADA ===
            $status_check = DB::table('transaksi')
                ->select('status_transaksi', DB::raw('COUNT(*) as count'))
                ->where('id_cabang', $id_cabang)
                ->whereRaw('DATE(tanggal_waktu) = ?', [$date])
                ->groupBy('status_transaksi')
                ->get();

            // === PENJUALAN ===
            $penjualan = DB::table('detail_transaksi')
                ->join('transaksi', 'detail_transaksi.id_transaksi', '=', 'transaksi.id_transaksi')
                ->join('produk', 'detail_transaksi.id_produk', '=', 'produk.id_produk')
                ->select(
                    'produk.nama_produk as produk',
                    DB::raw('SUM(detail_transaksi.jumlah_produk) as jumlah_produk'),
                    DB::raw('AVG(detail_transaksi.harga_item) as harga_item'),
                    DB::raw('SUM(detail_transaksi.jumlah_produk * detail_transaksi.harga_item) as total_penjualan_produk')
                )
                ->where('transaksi.id_cabang', $id_cabang)
                ->whereRaw('DATE(transaksi.tanggal_waktu) = ?', [$date])
                ->whereRaw('LOWER(TRIM(transaksi.status_transaksi)) = ?', ['selesai'])
                ->groupBy('produk.nama_produk', 'produk.id_produk')
                ->get();

            $total_penjualan = $penjualan->sum('total_penjualan_produk');

            // === BAHAN BAKU ===
            $bahan_baku = DB::table('bahan_baku_harian')
                ->join('bahan_baku', 'bahan_baku_harian.id_bahan_baku', '=', 'bahan_baku.id_bahan_baku')
                ->select(
                    'bahan_baku.nama_bahan',
                    'bahan_baku.satuan',
                    'bahan_baku.harga_satuan',
                    'bahan_baku_harian.jumlah_pakai',
                    DB::raw('(bahan_baku.harga_satuan * bahan_baku_harian.jumlah_pakai) as modal_produk')
                )
                ->where('bahan_baku_harian.id_cabang', $id_cabang)
                ->whereRaw('DATE(bahan_baku_harian.tanggal) = ?', [$date])
                ->get();

            $total_modal = $bahan_baku->sum('modal_produk');

            // === PENGELUARAN ===
            $non_cicilan = DB::table('pengeluaran')
                ->join('jenis_pengeluaran', 'pengeluaran.id_jenis', '=', 'jenis_pengeluaran.id_jenis')
                ->select(
                    'pengeluaran.id_pengeluaran',
                    'pengeluaran.tanggal',
                    'pengeluaran.jumlah',
                    'pengeluaran.cicilan_harian',
                    'jenis_pengeluaran.jenis_pengeluaran as jenis',
                    'pengeluaran.keterangan'
                )
                ->where('pengeluaran.id_cabang', $id_cabang)
                ->where('pengeluaran.cicilan_harian', 0)
                ->whereDate('pengeluaran.tanggal', $date)
                ->get();

            $cicilan = DB::table('pengeluaran')
                ->join('jenis_pengeluaran', 'pengeluaran.id_jenis', '=', 'jenis_pengeluaran.id_jenis')
                ->select(
                    'pengeluaran.id_pengeluaran',
                    'pengeluaran.tanggal',
                    'pengeluaran.jumlah',
                    'pengeluaran.cicilan_harian',
                    'jenis_pengeluaran.jenis_pengeluaran as jenis',
                    'pengeluaran.keterangan'
                )
                ->where('pengeluaran.id_cabang', $id_cabang)
                ->where('pengeluaran.cicilan_harian', '>', 0)
                ->whereDate('pengeluaran.tanggal', '<=', $date)
                ->whereMonth('pengeluaran.tanggal', Carbon::parse($date)->month)
                ->whereYear('pengeluaran.tanggal', Carbon::parse($date)->year)
                ->get();

            $pengeluaran = $non_cicilan->merge($cicilan);

            $pengeluaran_harian = 0;
            $total_non_installment = 0;
            $pengeluaran_detail = [];

            foreach ($pengeluaran as $item) {
                $tanggalMulai = Carbon::parse($item->tanggal);
                $tanggalSekarang = Carbon::parse($date);
                $is_installment = $item->cicilan_harian > 0;

                if ($is_installment) {
                    $daysInMonth = $tanggalMulai->daysInMonth;
                    $tanggalAkhir = $tanggalMulai->copy()->addDays($daysInMonth - 1);

                    if ($tanggalSekarang->between($tanggalMulai, $tanggalAkhir)) {
                        $harian = $item->cicilan_harian;
                    } else {
                        $harian = 0;
                    }
                } else {
                    $harian = $item->jumlah;
                    $total_non_installment += $item->jumlah;
                }

                $pengeluaran_harian += $harian;

                $pengeluaran_detail[] = [
                    'id_pengeluaran' => $item->id_pengeluaran,
                    'tanggal' => $item->tanggal,
                    'jumlah' => $item->jumlah,
                    'jenis' => $item->jenis,
                    'keterangan' => $item->keterangan,
                    'cicilan_harian' => $harian,
                    'is_installment' => $is_installment,
                    'is_today_expense' => $tanggalSekarang->isSameDay($tanggalMulai),
                ];
            }

            // === ONLOAN ===
            $onloan = DB::table('transaksi')
                ->where('id_cabang', $id_cabang)
                ->whereRaw('DATE(tanggal_waktu) = ?', [$date])
                ->whereRaw('LOWER(TRIM(status_transaksi)) = ?', ['onloan'])
                ->sum('total_harga');

            // === LABA & NETT ===
            $laba_harian = $total_penjualan - $total_modal;
            $nett_income = $laba_harian - $pengeluaran_harian;

            // === WARNING ===
            $peringatan = null;
            if (($total_penjualan + $onloan) < ($total_modal + $pengeluaran_harian)) {
                $peringatan = "⚠️ Pendapatan hari ini lebih kecil dari total pengeluaran dan modal";
            }

            // === INFO CABANG ===
            $cabang_info = DB::table('cabang')
                ->where('id_cabang', $id_cabang)
                ->select('nama_cabang', 'alamat')
                ->first();

            return response()->json([
                'tanggal' => $date,
                'cabang' => $cabang_info,
                'penjualan' => [
                    'detail' => $penjualan,
                    'total_penjualan' => $total_penjualan
                ],
                'bahan_baku' => [
                    'detail' => $bahan_baku,
                    'total_modal_bahan_baku' => $total_modal
                ],
                'pengeluaran' => [
                    'detail' => $pengeluaran_detail,
                    'cicilan_harian' => $pengeluaran_harian,
                    'total_non_installment' => $total_non_installment
                ],
                'onloan' => $onloan,
                'penjualan_harian' => $total_penjualan,
                'modal_bahan_baku' => $total_modal,
                'pengeluaran_harian' => $pengeluaran_harian,
                'laba_harian' => $laba_harian,
                'nett_income' => $nett_income,
                'peringatan' => $peringatan
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Terjadi kesalahan saat mengambil data',
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
        }
    }

    public function updateOrderStatusBranch(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $id_cabang = $user->id_cabang;
            $status = $request->input('status_transaksi');

            // Validasi akses cabang
            if (!$id_cabang) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak: Admin tidak terasosiasi dengan cabang'
                ], 403);
            }

            $updated = DB::table('transaksi')
                ->where('id_transaksi', $id)
                ->where('id_cabang', $id_cabang) // Hanya bisa update transaksi di cabangnya
                ->update([
                    'status_transaksi' => $status,
                    'updated_at' => now()
                ]);

            if ($updated) {
                return response()->json([
                    'success' => true,
                    'message' => 'Status order berhasil diperbarui'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan atau tidak memiliki akses'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getBranchInfo()
    {
        try {
            $user = Auth::user();
            $id_cabang = $user->id_cabang;

            if (!$id_cabang) {
                return response()->json([
                    'error' => 'Admin tidak terasosiasi dengan cabang'
                ], 404);
            }

            $cabang = DB::table('cabang')
                ->where('id_cabang', $id_cabang)
                ->select('id_cabang', 'nama_cabang', 'alamat', 'telepon')
                ->first();

            return response()->json([
                'success' => true,
                'cabang' => $cabang
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data cabang',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
