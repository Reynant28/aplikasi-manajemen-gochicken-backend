<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProdukModel extends Model
{
    use HasFactory;
    protected $table = 'produk';
    protected $primaryKey = 'id_produk';
    public $incrementing = false;
    protected $fillable = [
        'id_produk',
        'nama_produk',
        'deskripsi',
        'harga',
        'id_stock_cabang',
        'gambar_produk',
    ];

    public function stockCabang()
    {
        return $this->hasMany(StokCabangModel::class, 'id_stock_cabang');
    }
}
