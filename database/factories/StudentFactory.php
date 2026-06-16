<?php

namespace Database\Factories;

use App\Enums\CareStatus;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'student_code' => strtoupper(fake()->bothify('BC#####')),
            'full_name' => fake()->name(),
            'care_status' => CareStatus::Stable,
        ];
    }
}
