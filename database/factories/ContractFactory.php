<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Contract;

class ContractFactory extends Factory
{
    protected $model = Contract::class;

    public function definition(): array
    {
        return [
            'type' => $this->faker->randomElement(['SPK','PKWT']),
            'unit_id' => 1,
            'person_name' => $this->faker->name(),
            'position' => $this->faker->jobTitle(),
            'start_date' => $this->faker->date(),
            'end_date' => $this->faker->date(),
            'salary' => $this->faker->numberBetween(5000000,15000000),
            'status' => $this->faker->randomElement(['draft','approved','signed']),
            'created_by' => 1,
        ];
    }
}
