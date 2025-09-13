<?php

namespace Database\Seeders;

use App\Models\KaryawanModel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class KaryawanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        KaryawanModel::insert([
            [
                'nama_karyawan'=>'Karyawan 1',
                'alamat'=>'Jl. Cibabat No. 01, Cimahi',
                'telepon'=>'089238677103',
                'gaji'=>3000000,
                'id_cabang'=>'1',
            ],

            [
                'nama_karyawan'=>'Karyawan 2',
                'alamat'=>'Jl. Gatot Subroto No. 25, Bandung',
                'telepon'=>'089238677104',
                'gaji'=>3500000,
                'id_cabang'=>'2',
            ],
        ]);
    }
}
