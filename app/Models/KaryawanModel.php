<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KaryawanModel extends Model
{
    protected $table = 'karyawan';
    protected $primaryKey = 'id_karyawan';
    public $incrementing = false;
    protected $fillable = [
        "id_karyawan",
        'id_cabang',
        'nama_karyawan',
        'alamat',
        'telepon',
        'gaji',
    ];

    public function cabang()
    {
        return $this->belongsTo(CabangModel::class, 'id_cabang');
    }
}
