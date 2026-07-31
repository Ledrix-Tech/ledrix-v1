<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'name' => $this->faker->name(),
    'email' => $this->faker->unique()->safeEmail(),
    'phone' => $this->faker->phoneNumber(),
];
    }
}
