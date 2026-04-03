<?php

namespace Database\Factories;

use App\Models\Alternative;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Alternative>
 */
class AlternativeFactory extends Factory
{
    protected $model = Alternative::class;

    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'text'        => $this->faker->sentence(),
            'is_correct'  => false,
        ];
    }

    public function correct(): static
    {
        return $this->state(['is_correct' => true]);
    }
}
