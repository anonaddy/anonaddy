<?php

namespace Database\Factories;

use App\Models\AdditionalUsername;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdditionalUsernameFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AdditionalUsername::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => $this->faker->uuid,
            'username' => $this->faker->userName.$this->faker->randomNumber(3)
        ];
    }
}
