<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Cek apakah akun admin sudah ada agar tidak dobel jika dijalankan ulang
        $exists = DB::table('users')->where('username', 'admin')->exists();

        if (!$exists) {
            DB::table('users')->insert([
                'name'       => 'Dustin Eiga',
                'username'   => 'admin',
                'password'   => Hash::make('admin123'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
