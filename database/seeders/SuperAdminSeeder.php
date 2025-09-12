<?php

namespace Database\Seeders;

use App\Models\UsersModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        UsersModel::create([
            'nama'=>'Admin',
            'email'=>'admin@gmail.com',
            'password'=>Hash::make('123'),
            'role'=>'super admin',
        ]);
    }
}
