<?php

namespace Database\Seeders;

use App\Models\CabangModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CabangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CabangModel::insert([
            [
                'nama_cabang'=>'Cabang Cimahi',
                'alamat'=>'Jl. Cibodas No. 27, Cimahi',
                'telepon'=>'089238677103',
                'password_cabang'=>Hash::make('cabangcimahi1'),
            ],

            [
                'nama_cabang'=>'Cabang Bandung',
                'alamat'=>'Jl. Sukasari No. 15, Bandung',
                'telepon'=>'089238677104',
                'password_cabang'=>Hash::make('cabangbandung1'),
            ],
        ]);
    }
}
