<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DetailPengeluaranModel extends Model
{
    use HasFactory;

    protected $table = 'detail_pengeluaran';
    protected $primaryKey = 'id_detail_pengeluaran';
    public $incrementing = false;

    protected $fillable = [
        'id_pengeluaran',
        'id_jenis',
        'id_bahan_baku',
        'jumlah_item',
        'harga_satuan',
        'total_harga',
    ];

    // Relasi ke pengeluaran
    public function pengeluaran()
    {
        return $this->belongsTo(PengeluaranModel::class, 'id_pengeluaran');
    }

    // Relasi ke jenis pengeluaran
    public function jenisPengeluaran()
    {
        return $this->belongsTo(JenisPengeluaranModel::class, 'id_jenis_pengeluaran');
    }

    // Relasi ke bahan baku (kalau ada tabel bahan_baku)
    public function bahanBaku()
    {
        return $this->belongsTo(BahanBakuModel::class, 'id_bahan_baku');
    }
}
