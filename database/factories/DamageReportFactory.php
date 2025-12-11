<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ReportStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DamageReport>
 */
class DamageReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'package_id' => 'PKG-'.fake()->unique()->numerify('#####'),
            'location' => fake()->address(),
            'description' => fake()->optional()->sentence(),
            'photo_path' => null,
            'status' => ReportStatus::Draft,
            'ai_severity' => null,
            'ai_damage_type' => null,
            'ai_value_impact' => null,
            'ai_liability' => null,
            'submitted_at' => null,
            'approved_at' => null,
            'approved_by' => null,
        ];
    }

    /**
     * Indicate that the report is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReportStatus::Draft,
            'submitted_at' => null,
            'approved_at' => null,
            'approved_by' => null,
        ]);
    }

    /**
     * Indicate that the report has been submitted.
     */
    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReportStatus::Submitted,
            'submitted_at' => now(),
            'approved_at' => null,
            'approved_by' => null,
        ]);
    }

    /**
     * Indicate that the report has been approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReportStatus::Approved,
            'submitted_at' => now()->subDay(),
            'approved_at' => now(),
            'approved_by' => User::factory()->supervisor(),
        ]);
    }

    /**
     * Indicate that the report has AI assessment data.
     */
    public function withAiAssessment(): static
    {
        return $this->state(fn (array $attributes) => [
            'ai_severity' => fake()->randomElement(['low', 'moderate', 'high', 'critical']),
            'ai_damage_type' => fake()->randomElement(['water damage', 'crush damage', 'tear', 'puncture']),
            'ai_value_impact' => fake()->randomElement(['minimal', 'partial', 'significant', 'total loss']),
            'ai_liability' => fake()->randomElement(['carrier', 'sender', 'recipient', 'unknown']),
        ]);
    }
}
