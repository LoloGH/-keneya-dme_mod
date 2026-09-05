<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Prescription>
 */
class PrescriptionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'doctor_id' => User::factory(),
            'issued_on' => now()->toDateString(),
            'status' => 'draft',
        ];
    }

    public function validated(): static
    {
        return $this->state(fn () => ['status' => 'validated', 'validated_at' => now()]);
    }
}
