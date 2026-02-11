<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Maharani',
        //     'email' => 'maharani@fukuryo.co.id',
        //     'password' => '12345678',
        // ]);
        $birth = '2004-11-09';

        User::factory()->create([
            'name' => 'Maharani',
            'email' => 'maharani@fukuryo.co.id',
            'date_birth' => $birth,
            'password' => Hash::make(
                Carbon::parse($birth)->format('dmY')
            ),
        ]);

        User::factory()->count(15)->create();

        $this->call([
            MenuSeeder::class,
            PermissionSeeder::class,
            MenuPermissionSeeder::class,
        ]);
    }
}
