<?php

use Faker\Generator as Faker;

$factory->define(App\Recipient::class, function (Faker $faker) {
    return [
        'user_id' => $faker->uuid,
        'email' => $faker->safeEmail,
        'email_verified_at' => now()
    ];
});
