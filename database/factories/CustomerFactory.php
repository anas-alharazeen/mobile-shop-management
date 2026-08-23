<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'code' => Customer::generateCode(),
            'name' => $this->faker->name(),
            'phone' => $this->faker->optional()->unique()->phoneNumber(),
            'whatsapp' => $this->faker->optional()->phoneNumber(),
            'email' => $this->faker->optional()->unique()->safeEmail(),
            'address' => $this->faker->optional()->address(),
            'notes' => $this->faker->optional()->sentence(),
            'is_active' => $this->faker->boolean(80),
        ];
    }
}
