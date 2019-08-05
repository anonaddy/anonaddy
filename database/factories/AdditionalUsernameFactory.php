<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;

$factory->define(App\AdditionalUsername::class, function (Faker $faker) {
    return [
        'user_id' => $faker->uuid,
        'username' => $faker->userName
    ];
});
