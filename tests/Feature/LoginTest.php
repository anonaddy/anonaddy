<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = factory(User::class)->create([
            'username' => 'johndoe',
            'password' => Hash::make('mypassword')
        ]);
        $this->user->recipients()->save($this->user->defaultRecipient);
    }

    /** @test */
    public function user_can_login_successfully()
    {
        $response = $this->post('/login', [
            'username' => 'johndoe',
            'password' => 'mypassword'
        ]);

        $response
            ->assertRedirect('/')
            ->assertSessionHasNoErrors();
    }
}
