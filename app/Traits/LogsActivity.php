<?php
// app/Traits/LogsActivity.php

namespace App\Traits;

use App\Models\ActivityModel;
use Illuminate\Support\Facades\Auth;

trait LogsActivity
{
    protected static function bootLogsActivity()
    {
        static::created(function ($model) {
            // Get user ID safely - check if user is authenticated
            $userId = Auth::check() ? Auth::id() : null;

            ActivityModel::create([
                'type' => 'created',
                'model_type' => get_class($model),
                'id_model' => $model->getKey(),
                'description' => "Created " . class_basename($model) . ": " . $model->getActivityDescription(),
                'id_user' => $userId,
                'id_cabang' => $model->id_cabang ?? null,
                'new_data' => $model->toArray()
            ]);
        });

        static::updated(function ($model) {
            // Get user ID safely - check if user is authenticated
            $userId = Auth::check() ? Auth::id() : null;

            ActivityModel::create([
                'type' => 'updated',
                'model_type' => get_class($model),
                'id_model' => $model->getKey(),
                'description' => "Updated " . class_basename($model) . ": " . $model->getActivityDescription(),
                'id_user' => $userId,
                'id_cabang' => $model->cabang_id ?? $model->id_cabang ?? null,
                'old_data' => $model->getOriginal(),
                'new_data' => $model->getChanges()
            ]);
        });

        // Optional: Add deleted event if you want to track deletions
        static::deleted(function ($model) {
            $userId = Auth::check() ? Auth::id() : null;

            ActivityModel::create([
                'type' => 'deleted',
                'model_type' => get_class($model),
                'id_model' => $model->getKey(),
                'description' => "Deleted " . class_basename($model) . ": " . $model->getActivityDescription(),
                'id_user' => $userId,
                'id_cabang' => $model->cabang_id ?? $model->id_cabang ?? null,
                'old_data' => $model->toArray()
            ]);
        });
    }

    // Default method that models can override
    public function getActivityDescription()
    {
        if (isset($this->nama_karyawan)) return $this->nama_karyawan;
        if (isset($this->nama_produk)) return $this->nama_produk;
        if (isset($this->name)) return $this->name;
        if (isset($this->keterangan)) return $this->keterangan;
        if (isset($this->nama)) return $this->nama;
        return "Item #" . $this->getKey();
    }
}
