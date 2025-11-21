<?php

namespace App\Models;

use App\Traits\LogsActivity;
use App\Models\DetailPengeluaranModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BahanBakuPakaiModel extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'bahan_baku_harian';
    protected $primaryKey = 'id_pemakaian';
    public $incrementing = true;

    protected $fillable = [
        'tanggal',
        'jumlah_pakai',
        'catatan',
        'id_bahan_baku',
        'id_cabang',
    ];

    // Relasi: satu bahan baku bisa muncul di banyak detail pengeluaran
    public function detailPengeluaran()
    {
        return $this->hasMany(DetailPengeluaranModel::class, 'id_bahan_baku', 'id_bahan_baku');
    }

    // Relasi: bahan baku
    public function bahanBaku()
    {
        return $this->belongsTo(BahanBakuModel::class, 'id_bahan_baku');
    }
}
