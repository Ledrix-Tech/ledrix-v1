<?php

namespace Database\Factories;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Admin> */
class AdminFactory extends Factory
{
    protected $model = Admin::class;

    public function definition(): array
    {
        return [
            'name'     => $this->faker->name(),
            'email'    => $this->faker->unique()->safeEmail(),
            'password' => 'password',
            'role'     => 'admin',
            'tenant_id'=> 1,
        ];
    }

    public function finance(): static
    {
        return $this->state(fn () => ['role' => 'finance']);
    }
}
