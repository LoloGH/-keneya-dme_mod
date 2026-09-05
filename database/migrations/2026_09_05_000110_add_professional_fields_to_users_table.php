<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Champs professionnels des utilisateurs.
 *
 * Correspondance FHIR visée : Practitioner (§44). Ces colonnes restent
 * additives : lors de la phase 2, la table `users` pourra être remplacée
 * par celle de Keneya Workflow sans perte de sens métier.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('matricule')->nullable()->unique()->after('id');
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('title')->nullable()->after('last_name');       // Dr, Pr, M., Mme
            $table->string('speciality')->nullable()->after('title');
            $table->string('phone')->nullable()->after('email');
            $table->foreignId('service_id')->nullable()->after('phone')->constrained('services')->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('service_id');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');

            $table->index('is_active');
            $table->index('last_name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('service_id');
            $table->dropColumn([
                'matricule', 'first_name', 'last_name', 'title', 'speciality',
                'phone', 'is_active', 'last_login_at', 'last_login_ip',
            ]);
        });
    }
};
