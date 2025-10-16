<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\RecruitmentRequest;

class RecruitmentRequestFactory extends Factory
{
    protected $model = RecruitmentRequest::class;

    public function definition(): array
    {
        return [
            'unit_id' => 1,
            'title' => $this->faker->sentence(3),
            'position' => $this->faker->jobTitle(),
            'headcount' => $this->faker->numberBetween(1,5),
            'justification' => $this->faker->paragraph(),
            'status' => $this->faker->randomElement(['draft','submitted','approved','rejected']),
            'requested_by' => 1,
        ];
    }
}
