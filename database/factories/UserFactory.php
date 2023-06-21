<?php

namespace Database\Factories;

use App\Models\Recipient;
use App\Models\User;
use App\Models\Username;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'default_username_id' => Username::factory(),
            'banner_location' => 'top',
            'bandwidth' => 0,
            'default_recipient_id' => Recipient::factory(),
            'use_reply_to' => 0,
            'password' => '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', // secret
            'remember_token' => Str::random(10),
        ];
    }
}
