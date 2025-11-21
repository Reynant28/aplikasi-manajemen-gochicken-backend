<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\UsersModel;
use App\Models\CabangModel;
use App\Models\ProdukModel;

use Illuminate\Http\Request;
use App\Models\ActivityModel;
use App\Models\KaryawanModel;
use App\Models\PengeluaranModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    /**
     * ✨ NEW: Month-to-Month Comparison
     */
    public function monthComparison(Request $request)
    {
        $user = $request->user();

        // Get current month and previous month
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        $previousMonth = Carbon::now()->subMonth()->month;
        $previousYear = Carbon::now()->subMonth()->year;

        // Build base query
        $baseQuery = DB::table('transaksi');

        // Filter by cabang for admin cabang
        if ($user->role === 'admin cabang') {
            $baseQuery->where('id_cabang', $user->id_cabang);
        }

        // Current month stats - only completed transactions
        $currentRevenue = (clone $baseQuery)
            ->whereYear('tanggal_waktu', $currentYear)
            ->whereMonth('tanggal_waktu', $currentMonth)
            ->where('status_transaksi', 'selesai')
            ->sum('total_harga');

        $currentTransactions = (clone $baseQuery)
            ->whereYear('tanggal_waktu', $currentYear)
            ->whereMonth('tanggal_waktu', $currentMonth)
            ->where('status_transaksi', 'selesai')
            ->count();

        // Previous month stats - only completed transactions
        $previousRevenue = (clone $baseQuery)
            ->whereYear('tanggal_waktu', $previousYear)
            ->whereMonth('tanggal_waktu', $previousMonth)
            ->where('status_transaksi', 'selesai')
            ->sum('total_harga');

        $previousTransactions = (clone $baseQuery)
            ->whereYear('tanggal_waktu', $previousYear)
            ->whereMonth('tanggal_waktu', $previousMonth)
            ->where('status_transaksi', 'selesai')
            ->count();

        // Calculate percentage changes
        $revenueChange = $previousRevenue > 0
            ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100
            : ($currentRevenue > 0 ? 100 : 0); // If no previous revenue but current exists, show 100% growth

        $transactionChange = $previousTransactions > 0
            ? (($currentTransactions - $previousTransactions) / $previousTransactions) * 100
            : ($currentTransactions > 0 ? 100 : 0);

        // Calculate average per transaction
        $avgTransaction = $currentTransactions > 0
            ? $currentRevenue / $currentTransactions
            : 0;

        $prevAvgTransaction = $previousTransactions > 0
            ? $previousRevenue / $previousTransactions
            : 0;

        $avgChange = $prevAvgTransaction > 0
            ? (($avgTransaction - $prevAvgTransaction) / $prevAvgTransaction) * 100
            : ($avgTransaction > 0 ? 100 : 0);

        return response()->json([
            'status' => 'success',
            'data' => [
                'current_revenue' => (float) $currentRevenue,
                'previous_revenue' => (float) $previousRevenue,
                'revenue_change' => (float) $revenueChange,
                'current_transactions' => (int) $currentTransactions,
                'previous_transactions' => (int) $previousTransactions,
                'transaction_change' => (float) $transactionChange,
                'avg_transaction' => (float) $avgTransaction,
                'prev_avg_transaction' => (float) $prevAvgTransaction,
                'avg_change' => (float) $avgChange,
            ],
        ]);
    }

    public function monthComparisonCabang(Request $request)
    {
        try {
            $user = $request->user();

            // Validate user has cabang access if they're admin cabang
            if ($user->role === 'admin cabang' && !$user->id_cabang) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Admin cabang tidak memiliki akses cabang'
                ], 403);
            }

            // Get current month and previous month
            $currentMonth = Carbon::now()->month;
            $currentYear = Carbon::now()->year;
            $previousMonth = Carbon::now()->subMonth()->month;
            $previousYear = Carbon::now()->subMonth()->year;

            // Build base query
            $baseQuery = DB::table('transaksi');

            // Filter by cabang for admin cabang
            if ($user->role === 'admin cabang') {
                $baseQuery->where('id_cabang', $user->id_cabang);
            }

            // Current month stats - only completed transactions
            $currentRevenue = (clone $baseQuery)
                ->whereYear('tanggal_waktu', $currentYear)
                ->whereMonth('tanggal_waktu', $currentMonth)
                ->where('status_transaksi', 'selesai')
                ->sum('total_harga');

            $currentTransactions = (clone $baseQuery)
                ->whereYear('tanggal_waktu', $currentYear)
                ->whereMonth('tanggal_waktu', $currentMonth)
                ->where('status_transaksi', 'selesai')
                ->count();

            // Previous month stats - only completed transactions
            $previousRevenue = (clone $baseQuery)
                ->whereYear('tanggal_waktu', $previousYear)
                ->whereMonth('tanggal_waktu', $previousMonth)
                ->where('status_transaksi', 'selesai')
                ->sum('total_harga');

            $previousTransactions = (clone $baseQuery)
                ->whereYear('tanggal_waktu', $previousYear)
                ->whereMonth('tanggal_waktu', $previousMonth)
                ->where('status_transaksi', 'selesai')
                ->count();

            // Calculate percentage changes
            $revenueChange = $previousRevenue > 0
                ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100
                : ($currentRevenue > 0 ? 100 : 0); // If no previous revenue but current exists, show 100% growth

            $transactionChange = $previousTransactions > 0
                ? (($currentTransactions - $previousTransactions) / $previousTransactions) * 100
                : ($currentTransactions > 0 ? 100 : 0);

            // Calculate average per transaction
            $avgTransaction = $currentTransactions > 0
                ? $currentRevenue / $currentTransactions
                : 0;

            $prevAvgTransaction = $previousTransactions > 0
                ? $previousRevenue / $previousTransactions
                : 0;

            $avgChange = $prevAvgTransaction > 0
                ? (($avgTransaction - $prevAvgTransaction) / $prevAvgTransaction) * 100
                : ($avgTransaction > 0 ? 100 : 0);

            // Get cabang info if admin cabang
            $cabangInfo = null;
            if ($user->role === 'admin cabang') {
                $cabangInfo = DB::table('cabang')
                    ->where('id_cabang', $user->id_cabang)
                    ->select('nama_cabang', 'alamat')
                    ->first();
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'current_revenue' => (float) $currentRevenue,
                    'previous_revenue' => (float) $previousRevenue,
                    'revenue_change' => (float) round($revenueChange, 2),
                    'current_transactions' => (int) $currentTransactions,
                    'previous_transactions' => (int) $previousTransactions,
                    'transaction_change' => (float) round($transactionChange, 2),
                    'avg_transaction' => (float) round($avgTransaction, 2),
                    'prev_avg_transaction' => (float) round($prevAvgTransaction, 2),
                    'avg_change' => (float) round($avgChange, 2),
                    'cabang_info' => $cabangInfo,
                    'period' => [
                        'current' => [
                            'month' => $currentMonth,
                            'year' => $currentYear,
                            'label' => Carbon::create()->month($currentMonth)->format('F Y')
                        ],
                        'previous' => [
                            'month' => $previousMonth,
                            'year' => $previousYear,
                            'label' => Carbon::create()->month($previousMonth)->format('F Y')
                        ]
                    ]
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat membandingkan data bulanan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✨ NEW: Declining Products (Top 3 products with declining sales)
     */
    public function decliningProducts(Request $request)
    {
        $user = $request->user();

        // Get current month and previous month
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        $previousMonth = Carbon::now()->subMonth()->month;
        $previousYear = Carbon::now()->subMonth()->year;

        // Current month sales
        $currentSalesQuery = DB::table('detail_transaksi')
            ->join('transaksi', 'detail_transaksi.id_transaksi', '=', 'transaksi.id_transaksi')
            ->join('produk', 'detail_transaksi.id_produk', '=', 'produk.id_produk')
            ->whereYear('transaksi.tanggal_waktu', $currentYear)
            ->whereMonth('transaksi.tanggal_waktu', $currentMonth);

        if ($user->role === 'admin cabang') {
            $currentSalesQuery->where('transaksi.id_cabang', $user->id_cabang);
        }

        $currentSales = $currentSalesQuery
            ->select(
                'produk.id_produk',
                'produk.nama_produk',
                DB::raw('SUM(detail_transaksi.jumlah_produk) as total_sales')
            )
            ->groupBy('produk.id_produk', 'produk.nama_produk')
            ->get()
            ->keyBy('id_produk');

        // Previous month sales
        $previousSalesQuery = DB::table('detail_transaksi')
            ->join('transaksi', 'detail_transaksi.id_transaksi', '=', 'transaksi.id_transaksi')
            ->join('produk', 'detail_transaksi.id_produk', '=', 'produk.id_produk')
            ->whereYear('transaksi.tanggal_waktu', $previousYear)
            ->whereMonth('transaksi.tanggal_waktu', $previousMonth);

        if ($user->role === 'admin cabang') {
            $previousSalesQuery->where('transaksi.id_cabang', $user->id_cabang);
        }

        $previousSales = $previousSalesQuery
            ->select(
                'produk.id_produk',
                DB::raw('SUM(detail_transaksi.jumlah_produk) as total_sales')
            )
            ->groupBy('produk.id_produk')
            ->get()
            ->keyBy('id_produk');

        // Calculate declining products
        $decliningProducts = [];
        foreach ($currentSales as $productId => $current) {
            if (isset($previousSales[$productId])) {
                $currentTotal = $current->total_sales;
                $previousTotal = $previousSales[$productId]->total_sales;

                if ($currentTotal < $previousTotal) {
                    $declinePercentage = (($previousTotal - $currentTotal) / $previousTotal) * 100;

                    $decliningProducts[] = [
                        'id_produk' => $productId,
                        'nama_produk' => $current->nama_produk,
                        'current_sales' => (int) $currentTotal,
                        'previous_sales' => (int) $previousTotal,
                        'decline_percentage' => (float) $declinePercentage,
                    ];
                }
            }
        }

        // Sort by decline percentage (descending) and take top 3
        usort($decliningProducts, function ($a, $b) {
            return $b['decline_percentage'] <=> $a['decline_percentage'];
        });

        $decliningProducts = array_slice($decliningProducts, 0, 3);

        return response()->json([
            'status' => 'success',
            'data' => $decliningProducts,
        ]);
    }

    /**
     * ✨ NEW: Low Stock Alert (Products with stock < 5)
     */
    public function lowStockAlert(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'super admin') {
            // For super admin, show low stock from all branches
            $lowStockProducts = DB::table('stok_cabang')
                ->join('produk', 'stok_cabang.id_produk', '=', 'produk.id_produk')
                ->join('cabang', 'stok_cabang.id_cabang', '=', 'cabang.id_cabang')
                ->where('stok_cabang.jumlah_stok', '<', 5)
                ->select(
                    'produk.id_produk',
                    'produk.nama_produk',
                    'stok_cabang.jumlah_stok',
                    'cabang.nama_cabang'
                )
                ->orderBy('stok_cabang.jumlah_stok', 'asc')
                ->get();
        } else {
            // For admin cabang, show low stock from their branch only
            $lowStockProducts = DB::table('stok_cabang')
                ->join('produk', 'stok_cabang.id_produk', '=', 'produk.id_produk')
                ->where('stok_cabang.id_cabang', $user->id_cabang)
                ->where('stok_cabang.jumlah_stok', '<', 5)
                ->select(
                    'produk.id_produk',
                    'produk.nama_produk',
                    'stok_cabang.jumlah_stok'
                )
                ->orderBy('stok_cabang.jumlah_stok', 'asc')
                ->get();
        }

        return response()->json([
            'status' => 'success',
            'data' => $lowStockProducts,
        ]);
    }

    // Existing methods remain unchanged...

    public function cabangStats($id)
    {
        $totalProduk = DB::table('stok_cabang')->where('id_cabang', $id)->count();
        $tersedia = DB::table('stok_cabang')->where('id_cabang', $id)->where('jumlah_stok', '>', 0)->count();
        $todayCount = DB::table('transaksi')->whereDate('tanggal_waktu', Carbon::today())->where('id_cabang', $id)->count();
        $revenueMonth = DB::table('transaksi')->whereYear('tanggal_waktu', Carbon::now()->year)->whereMonth('tanggal_waktu', Carbon::now()->month)->where('id_cabang', $id)->sum('total_harga');

        $top = DB::table('detail_transaksi')
            ->join('transaksi', 'detail_transaksi.id_transaksi', '=', 'transaksi.id_transaksi')
            ->where('transaksi.id_cabang', $id)
            ->select('detail_transaksi.id_produk', DB::raw('SUM(detail_transaksi.jumlah_produk) as total_qty'))
            ->groupBy('detail_transaksi.id_produk')
            ->orderByDesc('total_qty')
            ->first();

        $topProductName = null;
        if ($top) {
            $prod = DB::table('produk')->where('id_produk', $top->id_produk)->first();
            $topProductName = $prod ? $prod->nama_produk : null;
        }

        $pendapatanHariIni = DB::table('transaksi')
            ->where('id_cabang', $id)
            ->whereDate('tanggal_waktu', Carbon::today())
            ->sum('total_harga');

        $pengeluaranHariIni = DB::table('pengeluaran')
            ->where('id_cabang', $id)
            ->whereDate('tanggal', Carbon::today())
            ->sum('jumlah');

        $estimasiLaba = $pendapatanHariIni - $pengeluaranHariIni;

        $revenueBreakdown = DB::table('transaksi')
            ->where('id_cabang', $id)
            ->whereDate('tanggal_waktu', Carbon::today())
            ->select('metode_pembayaran', DB::raw('SUM(total_harga) as total'))
            ->groupBy('metode_pembayaran')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_produk' => (int) $totalProduk,
                'produk_tersedia' => (int) $tersedia,
                'transactions_today' => (int) $todayCount,
                'revenue_month' => (float) $revenueMonth,
                'top_product' => $topProductName,
                'pendapatan_hari_ini' => (float) $pendapatanHariIni,
                'pengeluaran_hari_ini' => (float) $pengeluaranHariIni,
                'estimasi_laba' => (float) $estimasiLaba,
                'revenue_breakdown' => $revenueBreakdown,
            ],
        ]);
    }

    public function cabangDecliningProducts($id)
    {
        $user = request()->user();

        if ($user->role !== 'admin cabang' || $user->id_cabang != $id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }

        try {
            // Your declining products logic for cabang here
            $data = [];

            return response()->json([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch declining products data'
            ], 500);
        }
    }

    public function cabangLowStockAlert($id)
    {
        $user = request()->user();

        if ($user->role !== 'admin cabang' || $user->id_cabang != $id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }

        try {
            // Your low stock logic for cabang here
            $data = [];

            return response()->json([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch low stock data'
            ], 500);
        }
    }

    public function globalStats()
    {
        $totalProduk = DB::table('produk')->count();
        $todayCount = DB::table('transaksi')->whereDate('tanggal_waktu', Carbon::today())->count();
        $revenueMonth = DB::table('transaksi')
            ->whereYear('tanggal_waktu', Carbon::now()->year)
            ->whereMonth('tanggal_waktu', Carbon::now()->month)
            ->sum('total_harga');

        $top = DB::table('detail_transaksi')
            ->join('transaksi', 'detail_transaksi.id_transaksi', '=', 'transaksi.id_transaksi')
            ->select('detail_transaksi.id_produk', DB::raw('SUM(detail_transaksi.jumlah_produk) as total_qty'))
            ->groupBy('detail_transaksi.id_produk')
            ->orderByDesc('total_qty')
            ->first();

        $topProductName = null;
        if ($top) {
            $prod = DB::table('produk')->where('id_produk', $top->id_produk)->first();
            $topProductName = $prod ? $prod->nama_produk : null;
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_produk' => (int) $totalProduk,
                'transactions_today' => (int) $todayCount,
                'revenue_month' => (float) $revenueMonth,
                'top_product' => $topProductName,
            ],
        ]);
    }

    public function globalChart(Request $request)
    {
        $filter = $request->query('filter', 'tahun');

        $pendapatan = DB::table('transaksi')
            ->selectRaw("DATE(tanggal_waktu) as tanggal, SUM(total_harga) as total")
            ->when($filter === 'minggu', function ($q) {
                $q->whereBetween('tanggal_waktu', [now()->startOfWeek(Carbon::SUNDAY), now()->endOfWeek(Carbon::SATURDAY)]);
            })
            ->when($filter === 'bulan', function ($q) {
                $q->whereYear('tanggal_waktu', now()->year)->whereMonth('tanggal_waktu', now()->month);
            })
            ->when($filter === 'tahun', function ($q) {
                $q->whereYear('tanggal_waktu', now()->year);
            })
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get();

        $pengeluaran = DB::table('pengeluaran')
            ->selectRaw("DATE(tanggal) as tanggal, SUM(jumlah) as total")
            ->when($filter === 'minggu', function ($q) {
                $q->whereBetween('tanggal', [now()->startOfWeek(Carbon::SUNDAY), now()->endOfWeek(Carbon::SATURDAY)]);
            })
            ->when($filter === 'bulan', function ($q) {
                $q->whereYear('tanggal', now()->year)->whereMonth('tanggal', now()->month);
            })
            ->when($filter === 'tahun', function ($q) {
                $q->whereYear('tanggal', now()->year);
            })
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'pendapatan' => $pendapatan,
                'pengeluaran' => $pengeluaran,
            ]
        ]);
    }

    public function cabangChart(Request $request, $id)
    {
        $filter = $request->query('filter', 'tahun');

        $pendapatan = DB::table('transaksi')
            ->selectRaw("DATE(tanggal_waktu) as tanggal, SUM(total_harga) as total")
            ->where('id_cabang', $id)
            ->when($filter === 'minggu', function ($q) {
                $q->whereBetween('tanggal_waktu', [
                    now()->startOfWeek(Carbon::SUNDAY), now()->endOfWeek(Carbon::SATURDAY)
                ]);
            })
            ->when($filter === 'bulan', function ($q) {
                $q->whereYear('tanggal_waktu', now()->year)
                    ->whereMonth('tanggal_waktu', now()->month);
            })
            ->when($filter === 'tahun', function ($q) {
                $q->whereYear('tanggal_waktu', now()->year);
            })
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get();

        $pengeluaran = DB::table('pengeluaran')
            ->selectRaw("DATE(tanggal) as tanggal, SUM(jumlah) as total")
            ->where('id_cabang', $id)
            ->when($filter === 'minggu', function ($q) {
                $q->whereBetween('tanggal', [
                    now()->startOfWeek(Carbon::SUNDAY), now()->endOfWeek(Carbon::SATURDAY)
                ]);
            })
            ->when($filter === 'bulan', function ($q) {
                $q->whereYear('tanggal', now()->year)
                    ->whereMonth('tanggal', now()->month);
            })
            ->when($filter === 'tahun', function ($q) {
                $q->whereYear('tanggal', now()->year);
            })
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'pendapatan' => $pendapatan,
                'pengeluaran' => $pengeluaran,
            ]
        ]);
    }

    public function globalActivities(Request $request)
    {
        $user = $request->user();

        if (!$user || $user->role !== 'super admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }

        // Use the ActivitiesModel to get logged activities
        $recentActivities = ActivityModel::with('user')
            ->orderBy('created_at', 'desc')
            ->take(15)
            ->get()
            ->map(function ($activity) {
                $description = $this->extractActivityDescription($activity->description, $activity->model_type);
                return [
                    'description' => $description,
                    'timestamp' => $activity->created_at->toISOString(),
                    'type' => $activity->type,
                    'model' => class_basename($activity->model_type),
                    'user' => $activity->user ? $activity->user->nama : 'System',
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'status' => 'success',
            'data' => $recentActivities
        ]);
    }

    public function userActivities(Request $request)
    {
        $user = $request->user();

        if (!$user || $user->role !== 'admin cabang') {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }

        $cabangId = $user->id_cabang;

        try {
            // Optimized query dengan select hanya kolom yang diperlukan
            $recentActivities = ActivityModel::select([
                    'activities.description',
                    'activities.created_at',
                    'activities.type',
                    'activities.model_type',
                    'users.nama as user_name'
                ])
                ->join('users', 'activities.id_user', '=', 'users.id_user')
                ->where('users.id_cabang', $cabangId)
                ->where('users.role', $user->role)
                ->orderBy('activities.created_at', 'desc')
                ->limit(15)
                ->get()
                ->map(function ($activity) {
                    $description = $this->extractActivityDescription($activity->description, $activity->model_type);
                    return [
                        'description' => $description,
                        'timestamp' => $activity->created_at->toISOString(),
                        'type' => $activity->type,
                        'model' => class_basename($activity->model_type),
                        'user' => $activity->user_name ?: 'System'
                    ];
                })
                ->values()
                ->all();

            return response()->json([
                'status' => 'success',
                'data' => $recentActivities
            ]);

        } catch (\Exception $e) {
            Log::error('User Activities Error: ' . $e->getMessage());

            // Return empty array jika ada error
            return response()->json([
                'status' => 'success',
                'data' => []
            ]);
        }
    }

    /**
     * Extract description by removing only the model name but keeping the action
     */
    private function extractActivityDescription($description, $modelType)
    {
        if (empty($description)) {
            return $description;
        }

        // If model type is available, remove only the model name
        if (!empty($modelType)) {
            $modelName = class_basename($modelType);

            // Remove the model name but keep the action
            // Pattern: remove "ModelName:" or " ModelName:"
            $pattern = '/\s*' . preg_quote($modelName, '/') . ':\s*/';
            $cleanDescription = preg_replace($pattern, ': ', $description);

            // If the replacement worked and it's different from original, return it
            if ($cleanDescription !== $description) {
                return $cleanDescription;
            }
        }

        // Fallback: try to remove common model patterns but keep action
        $patterns = [
            '/([A-Z][a-zA-Z]+)Model:\s*/', // Remove "ModelNameModel:" but keep action
            '/([A-Z][a-zA-Z]+):\s*/',      // Remove "ModelName:" but keep action
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $description)) {
                // Replace model name with colon but keep everything else
                $cleanDescription = preg_replace($pattern, '$1: ', $description);
                return $cleanDescription;
            }
        }

        // If no patterns match, return original description
        return $description;
    }
}
