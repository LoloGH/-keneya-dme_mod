<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal d'audit (§30).
 *
 * La table conserve le schéma attendu par spatie/laravel-activitylog
 * (log_name, description, subject, causer, properties) et y ajoute les
 * colonnes exigées par la spécification : rôle, patient concerné, adresse
 * IP et résultat de l'action. Un seul journal est donc maintenu : les
 * modifications de modèles et les accès aux dossiers y cohabitent, ce qui
 * évite deux systèmes d'audit parallèles.
 *
 * Append-only : aucune route ni policy n'autorise la modification ou la
 * suppression d'une entrée. Le modèle AuditLog bloque également ces
 * opérations au niveau applicatif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->string('log_name')->nullable()->index();
            $table->text('description');
            $table->nullableMorphs('subject', 'subject');
            $table->string('event')->nullable();
            $table->nullableMorphs('causer', 'causer');
            $table->json('properties')->nullable();
            $table->string('batch_uuid')->nullable();

            // Colonnes propres au contexte médical Keneya-DME
            $table->string('causer_role')->nullable();
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->string('action')->nullable();      // viewed, created, updated, downloaded, denied…
            $table->enum('outcome', ['allowed', 'denied', 'failed'])->default('allowed');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('route')->nullable();

            $table->timestamps();

            $table->index('patient_id');   // §59
            $table->index('created_at');
            $table->index('outcome');
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log');
    }
};
