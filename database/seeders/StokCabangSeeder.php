<?php

namespace Database\Seeders;

use App\Models\StokCabangModel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StokCabangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        StokCabangModel::insert([
            [
                'id_cabang' => 1,
                'id_produk' => 1,
                'jumlah_stok' => 30,
            ],
            [
                'id_cabang' => 1,
                'id_produk' => 2,
                'jumlah_stok' => 20,
            ],
        ]);
    }
}
