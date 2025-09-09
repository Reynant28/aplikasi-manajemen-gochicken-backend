<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StokCabangModel extends Model
{
    use HasFactory;

    protected $table = 'stok_cabang';
    protected $primaryKey = 'id_stock_cabang';
    public $incrementing = false;

    protected $fillable = [
        'id_stock_cabang',
        'id_cabang',
        'id_produk',
        'jumlah_stok',
    ];

    // Relasi ke produk
    public function produk()
    {
        return $this->belongsTo(ProdukModel::class, 'id_produk');
    }

    // Relasi ke cabang (kalau ada model Cabang)
    public function cabang()
    {
        return $this->belongsTo(CabangModel::class, 'id_cabang');
    }
}
