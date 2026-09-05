<?php

declare(strict_types=1);

namespace Tests;

use App\Models\User;
use App\Support\Rbac;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\ServiceSeeder;
use Database\Seeders\SmsTemplateSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Crée les rôles, permissions, services et modèles SMS nécessaires à
     * la quasi-totalité des tests. Appelé explicitement plutôt qu'en
     * setUp() afin qu'un test unitaire pur n'ait pas à payer ce coût.
     */
    protected function seedReferenceData(): void
    {
        $this->seed([
            RoleAndPermissionSeeder::class,
            ServiceSeeder::class,
            SmsTemplateSeeder::class,
        ]);
    }

    /**
     * Fabrique un utilisateur actif portant un rôle donné.
     */
    protected function userWithRole(string $role = Rbac::ROLE_DOCTOR, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole($role);

        return $user->fresh();
    }
}
