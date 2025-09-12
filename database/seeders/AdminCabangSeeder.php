<?php

namespace Database\Seeders;

use App\Models\UsersModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AdminCabangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        UsersModel::insert([
            [
                'nama'=>'Admin Cabang Cimahi',
                'email'=>'admin_cimahi@gmail.com',
                'password'=>Hash::make('123'),
                'role'=>'admin cabang',
                'id_cabang'=>1,
            ],

            [
                'nama'=>'Admin Cabang Bandung',
                'email'=>'admin_bandung@gmail.com',
                'password'=>Hash::make('123'),
                'role'=>'admin cabang',
                'id_cabang'=>2,
            ],
        ]);
    }
}
