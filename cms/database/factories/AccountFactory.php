<?php

namespace Database\Factories;

use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        return [
            'role' => Account::EDITOR,
            'username' => 'user'.fake()->unique()->numberBetween(1, 999999),
            'password' => 'correct-horse-battery',
        ];
    }

    public function editor(): static
    {
        return $this->state(['role' => Account::EDITOR]);
    }

    public function superAdmin(): static
    {
        return $this->state(['role' => Account::SUPER_ADMIN]);
    }
}
