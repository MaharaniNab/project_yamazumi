<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

class MenuPermissionSeeder extends Seeder
{
       public function run(): void
    {
        // Ambil semua permission
        $permissions = Permission::where('guard_name', 'web')->get()->keyBy('name');

        // Kosongkan pivot
        DB::table('menu_permission')->truncate();
        $map = [

            1 => ['read_dashboard'],
            // USER MANAGEMENT
            3 => ['read_role', 'create_role', 'update_role', 'delete_role'],
            4 => ['read_user', 'create_user', 'update_user', 'delete_user'],
        ];

        /*
        |--------------------------------------------------------------------------
        | INSERT KE PIVOT
        |--------------------------------------------------------------------------
        */
        $insert = [];

        foreach ($map as $menuId => $perms) {
            foreach ($perms as $perm) {
                if (isset($permissions[$perm])) {
                    $insert[] = [
                        'menu_id'       => $menuId,
                        'permission_id' => $permissions[$perm]->id,
                    ];
                }
            }
        }

        DB::table('menu_permission')->insert($insert);
    }
}
