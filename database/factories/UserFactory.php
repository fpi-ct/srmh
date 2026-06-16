<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'access_code' => fake()->unique()->userName(),
            'full_name' => fake()->name(),
            'role_label' => 'Giảng viên',
            'role' => UserRole::Lecturer,
            'faculties' => null,
            'is_active' => true,
        ];
    }
}
