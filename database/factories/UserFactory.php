<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'nickname' => $this->faker->name(),
            'username' => $this->faker->unique()->userName(),
            'password' => static::$password ??= Hash::make('Password@123'),
            'remember_token' => Str::random(10),
            'role' => 'user',
            'is_suspended' => false,
            'suspended_by' => null,
            'suspended_at' => null,
            'restored_at' => null,
            'restored_by' => null,
        ];
    }
}
