<?php

namespace Database\Factories;

use App\Models\Attempt;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attempt>
 */
class AttemptFactory extends Factory
{
    protected $model = Attempt::class;

    public function definition(): array
    {
        return [
            'user_id'    => User::factory(),
            'exam_id'    => Exam::factory(),
            'score'      => $this->faker->numberBetween(0, 10),
            'percentage' => $this->faker->randomFloat(2, 0, 100),
        ];
    }
}
