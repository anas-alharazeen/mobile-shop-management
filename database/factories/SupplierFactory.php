<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'code' => Supplier::generateCode(),
            'name' => $this->faker->name(),
            'company_name' => $this->faker->optional()->company(),
            'phone' => $this->faker->unique()->phoneNumber(),
            'whatsapp' => $this->faker->optional()->phoneNumber(),
            'email' => $this->faker->optional()->unique()->safeEmail(),
            'address' => $this->faker->optional()->address(),
            'notes' => $this->faker->optional()->sentence(),
            'is_active' => $this->faker->boolean(80),
        ];
    }
}
