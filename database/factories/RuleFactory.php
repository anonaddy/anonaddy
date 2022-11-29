<?php

namespace Database\Factories;

use App\Models\Rule;
use Illuminate\Database\Eloquent\Factories\Factory;

class RuleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Rule::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->userName,
            'order' => $this->faker->randomNumber(1),
            'conditions' => [
                [
                    'type' => 'sender',
                    'match' => 'is exactly',
                    'values' => [
                        'will@anonaddy.com',
                    ],
                ],
            ],
            'actions' => [
                [
                    'type' => 'subject',
                    'value' => 'New Subject!',
                ],
            ],
        ];
    }
}
