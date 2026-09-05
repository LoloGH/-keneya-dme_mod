<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Support\Rbac;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Rôles et permissions (§31-32).
 *
 * Idempotent : le seeder peut être rejoué sans dupliquer ni perdre les
 * attributions existantes. Il est la traduction directe de App\Support\Rbac,
 * qui reste la source de vérité.
 */
class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Rbac::allPermissions() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach (Rbac::rolePermissions() as $roleName => $permissions) {
            Role::findOrCreate($roleName, 'web')->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
