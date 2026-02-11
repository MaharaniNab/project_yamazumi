<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        DB::table('menu')->insert([
            /*
            |--------------------------------------------------------------------------
            | MENU UTAMA
            |--------------------------------------------------------------------------
            */
            [
                'id' => 1,
                'name' => 'Dashboard',
                'icon' => 'home',
                'route' => 'dashboard',
                'parent_id' => null,
                'order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            /*
            |--------------------------------------------------------------------------
            | MANAJEMEN USER (PARENT)
            |--------------------------------------------------------------------------
            */
            [
                'id' => 2,
                'name' => 'Manajemen User',
                'icon' => 'cog-6-tooth',
                'route' => null,
                'parent_id' => null,
                'order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 3,
                'name' => 'Kelola Role',
                'icon' => 'user-group',
                'route' => 'manajemen.role',
                'parent_id' => 2,
                'order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 4,
                'name' => 'Kelola Pengguna',
                'icon' => 'user',
                'route' => 'manajemen.user',
                'parent_id' => 2,
                'order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
