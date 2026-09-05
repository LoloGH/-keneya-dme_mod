<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'doctor_id' => User::factory(),
            'scheduled_for' => now()->addDays(3)->setTime(9, 30),
            'duration_minutes' => 30,
            'reason' => 'Consultation de suivi',
            'status' => 'scheduled',
        ];
    }
}
