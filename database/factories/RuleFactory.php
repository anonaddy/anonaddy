<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;

$factory->define(App\Rule::class, function (Faker $faker) {
    return [
        'name' => $faker->userName,
        'order' => $faker->randomNumber(1),
        'conditions' => [
            [
                'type' => 'sender',
                'match' => 'is exactly',
                'values' => [
                    'will@anonaddy.com'
                ]
            ]
        ],
        'actions' => [
            [
                'type' => 'subject',
                'value' => 'New Subject!'
            ]
        ]
    ];
});
