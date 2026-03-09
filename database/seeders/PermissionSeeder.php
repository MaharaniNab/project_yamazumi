<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | DAFTAR PERMISSION
        |--------------------------------------------------------------------------
        */
        $permissions = [

            // ===========================
            // DASHBOARD
            // ===========================
            'read_dashboard',

            // ===========================
            // USER MANAGEMENT
            // ===========================
            'read_user',
            'create_user',
            'update_user',
            'delete_user',

            'read_role',
            'create_role',
            'update_role',
            'delete_role',
        ];

        /*
        |--------------------------------------------------------------------------
        | INSERT PERMISSION
        |--------------------------------------------------------------------------
        */
        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | ROLE IT (FULL ACCESS)
        |--------------------------------------------------------------------------
        */
        $role = Role::firstOrCreate([
            'name' => 'IE',
            'guard_name' => 'web',
        ]);

        $role->syncPermissions(Permission::where('guard_name', 'web')->get());

        /*
        |--------------------------------------------------------------------------
        | ASSIGN ROLE KE USER (OPSIONAL)
        |--------------------------------------------------------------------------
        */
        $user = User::find(1);
        if ($user) {
            $user->assignRole('IE');
        }
    }
}
