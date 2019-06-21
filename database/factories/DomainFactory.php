<?php

use Faker\Generator as Faker;

$factory->define(App\Domain::class, function (Faker $faker) {
    return [
        'user_id' => $faker->uuid,
        'domain' => $faker->domainName
    ];
});
