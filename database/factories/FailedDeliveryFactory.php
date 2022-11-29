<?php

namespace Database\Factories;

use App\Models\FailedDelivery;
use Illuminate\Database\Eloquent\Factories\Factory;

class FailedDeliveryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FailedDelivery::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'status' => '5.1.1',
            'code' => $this->faker->sentence(5),
        ];
    }
}
