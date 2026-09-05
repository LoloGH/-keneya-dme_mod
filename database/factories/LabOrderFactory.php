<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\LabOrder>
 */
class LabOrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'doctor_id' => User::factory(),
            'requested_at' => now(),
            'priority' => 'routine',
            'status' => 'requested',
        ];
    }
}
