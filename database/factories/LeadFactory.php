<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Seller;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'brand_id' => Brand::factory(),
            'seller_id' => Seller::factory(),
            'client_id' => Client::factory(),
            'name' => $this->faker->name(),
            'email' => $this->faker->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'status' => 'new',
        ];
    }
}
