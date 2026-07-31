<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BrandFactory extends Factory
{
   public function definition(): array
    {
        return [
            'brand_name' => $this->faker->company(),
            'brand_url'  => $this->faker->url(),
            'status'     => 'Active',
            'tenant_id'  => 1,
        ];
    }
}
