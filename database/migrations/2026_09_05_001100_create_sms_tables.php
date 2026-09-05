<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Service SMS transversal (§35).
 *
 * Ces tables ne référencent le domaine médical que par des colonnes
 * facultatives et non contraintes (`context_type`, `context_id`) : le
 * service SMS ne dépend d'aucun modèle du DME et peut donc être extrait
 * tel quel lors de la phase 2 pour devenir un service partagé de
 * Keneya Workflow.
 *
 * `patient_id` est en revanche indexé (§59) car l'historique SMS d'un
 * patient est consultable depuis son dossier.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // appointment_reminder, lab_result_available…
            $table->string('name');
            $table->text('body');            // supporte les variables {{ patient_name }}
            $table->json('variables')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('sms_messages', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('recipient');                 // numéro normalisé E.164
            $table->text('body');
            $table->string('sender')->nullable();

            $table->foreignId('sms_template_id')->nullable()
                ->constrained('sms_templates')->nullOnDelete();

            // Contexte métier — volontairement non contraint (découplage)
            $table->foreignId('patient_id')->nullable();
            $table->string('context_type')->nullable();
            $table->unsignedBigInteger('context_id')->nullable();

            $table->enum('status', ['pending', 'queued', 'sent', 'failed', 'cancelled'])
                ->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->dateTime('scheduled_for')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('failed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->string('gateway')->nullable();
            $table->string('gateway_message_id')->nullable();
            $table->json('gateway_response')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('patient_id');   // §59
            $table->index('status');       // §59
            $table->index('scheduled_for');
            $table->index(['context_type', 'context_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_messages');
        Schema::dropIfExists('sms_templates');
    }
};
