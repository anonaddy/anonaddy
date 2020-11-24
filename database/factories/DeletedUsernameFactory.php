<?php

namespace Database\Factories;

use App\Models\DeletedUsername;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeletedUsernameFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = DeletedUsername::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'username' => $this->faker->userName,
        ];
    }
}
