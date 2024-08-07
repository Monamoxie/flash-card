<?php

namespace Modules\Flashcard\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Flashcard\Models\Flashcard;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class FlashcardFactory extends Factory
{
    protected $model = Flashcard::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question' => fake()->sentence,
            'answer' => fake()->sentence
        ];
    }
}
