<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;

$factory->define(App\DeletedUsername::class, function (Faker $faker) {
    return [
        'username' => $faker->userName,
    ];
});
