<?php

use Faker\Generator as Faker;

$factory->define(App\Alias::class, function (Faker $faker) {
    return [
        'user_id' => $faker->uuid,
        'email' => $faker->userName.'@'.$faker->unique()->word.'.'.config('anonaddy.domain'),
        'local_part' => $faker->userName,
        'domain' => 'johndoe.'.config('anonaddy.domain'),
        'active' => true,
        'description' => $faker->sentence
    ];
});
