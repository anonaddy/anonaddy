<?php

namespace Database\Factories;

use App\Models\Alias;
use Illuminate\Database\Eloquent\Factories\Factory;

class AliasFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Alias::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $localPart = $this->faker->userName.$this->faker->randomNumber(2);

        return [
            'user_id' => $this->faker->uuid,
            'email' => $localPart.'@'.$this->faker->word.'.'.config('anonaddy.domain'),
            'local_part' => $localPart,
            'domain' => 'johndoe.'.config('anonaddy.domain'),
            'active' => true,
            'description' => $this->faker->sentence,
        ];
    }
}
