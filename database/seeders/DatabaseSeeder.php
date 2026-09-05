<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Amorçage de l'application.
 *
 * Les deux premiers seeders sont structurels et s'exécutent dans tous les
 * environnements. Les deux suivants ne créent des données qu'en local et
 * en test (§55).
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            ServiceSeeder::class,
            SmsTemplateSeeder::class,
            DemoUserSeeder::class,
            DemoMedicalDataSeeder::class,
        ]);
    }
}
