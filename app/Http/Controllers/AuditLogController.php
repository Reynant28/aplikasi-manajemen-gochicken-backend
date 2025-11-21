<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\ActivityModel;
use App\Models\ActivitiesModel;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user || $user->role !== 'super admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Hanya Super Admin yang dapat mengakses audit log.'
            ], 403);
        }

        try {
            $query = ActivityModel::with(['user', 'cabang'])
            ->whereHas('user', function ($q) {
                $q->where('role', '!=', 'kasir'); 
            })
            ->orderBy('created_at', 'desc');

            $query = $this->applyFilters($query, $request);

            $perPage = $request->get('per_page', 20);
            $auditLogs = $query->paginate($perPage);

            $transformedLogs = $auditLogs->getCollection()->map(function ($log) {
                return $this->transformAuditLog($log);
            });

            return response()->json([
                'status' => 'success',
                'data' => $transformedLogs,
                'meta' => [
                    'current_page' => $auditLogs->currentPage(),
                    'last_page' => $auditLogs->lastPage(),
                    'per_page' => $auditLogs->perPage(),
                    'total' => $auditLogs->total(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Audit log error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data audit log'
            ], 500);
        }
    }

    public function getFilters(Request $request)
    {
        $user = $request->user();

        if (!$user || $user->role !== 'super admin') {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        try {
            // Get unique types
            $types = ActivityModel::distinct()->pluck('type')->filter()->values();

            // Get unique models - handle cases where model_type might be null or empty
            $models = ActivityModel::distinct()
                ->whereNotNull('model_type')
                ->where('model_type', '!=', '')
                ->pluck('model_type')
                ->map(function ($model) {
                    // Extract just the class name without namespace
                    $parts = explode('\\', $model);
                    return end($parts);
                })
                ->filter()
                ->unique()
                ->values();

            // Get users who have activities
            $users = \App\Models\User::whereIn('id_user', ActivityModel::distinct()->pluck('id_user'))
                ->get(['id_user', 'nama'])
                ->map(function ($user) {
                    return [
                        'id_user' => $user->id_user,
                        'nama' => $user->nama ?: 'Unknown User'
                    ];
                });

            // Get date range
             $dateRange = [
                'min_date' => ActivityModel::whereHas('user', function ($q) {
                    $q->where('role', '!=', 'kasir');
                })->min('created_at'),
                'max_date' => ActivityModel::whereHas('user', function ($q) {
                    $q->where('role', '!=', 'kasir');
                })->max('created_at')
            ];

            $filters = [
                'types' => $types,
                'models' => $models,
                'users' => $users,
                'date_range' => $dateRange
            ];

            return response()->json([
                'status' => 'success',
                'data' => $filters
            ]);
        } catch (\Exception $e) {
            Log::error('Get filters error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil filter: ' . $e->getMessage()
            ], 500);
        }
    }

    private function applyFilters($query, Request $request)
    {
        $query->whereHas('user', function ($q) {
            $q->where('role', '!=', 'kasir');
        });

        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        if ($request->has('model') && $request->model !== 'all') {
            $modelClass = 'App\\Models\\' . $request->model;
            $query->where('model_type', $modelClass);
        }

        if ($request->has('user_id') && $request->user_id !== 'all') {
            $query->where('id_user', $request->user_id);
        }

        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', Carbon::parse($request->start_date));
        }
        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', Carbon::parse($request->end_date));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('description', 'like', "%{$search}%");
        }

        return $query;
    }

    private function transformAuditLog($log)
    {
        $modelName = class_basename($log->model_type);
        $cleanModelName = str_replace('Model', '', $modelName);


        $description = $log->description;
        $description = str_replace($modelName, $cleanModelName, $description);

        return [
            'id' => $log->id_activity,
            'type' => $log->type,
            'model' => $cleanModelName,
            'description' => $description,
            'user' => $log->user ? $log->user->nama : 'System',
            'cabang' => $log->cabang ? $log->cabang->nama_cabang : 'N/A',
            'timestamp' => $log->created_at->toISOString(),
            'old_data' => $log->old_data,
            'new_data' => $log->new_data,
            'changes' => $this->getDetailedChanges($log->old_data, $log->new_data, $log->type),
            'ip_address' => $log->ip_address ?? 'N/A',
            'user_agent' => $log->user_agent ?? 'N/A',
        ];
    }

    private function getDetailedChanges($oldData, $newData, $type)
    {
        if ($type === 'created') {
            return ['action' => 'Record created'];
        }

        if ($type === 'deleted') {
            return ['action' => 'Record deleted'];
        }

        if ($type === 'updated' && $oldData && $newData) {
            $changes = [];
            foreach ($newData as $key => $newValue) {
                $oldValue = $oldData[$key] ?? null;

                if ($oldValue != $newValue) {
                    $changes[] = [
                        'field' => $key,
                        'from' => $oldValue,
                        'to' => $newValue
                    ];
                }
            }
            return $changes;
        }

        return [];
    }
}
