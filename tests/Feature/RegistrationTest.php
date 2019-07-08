<?php

namespace Tests\Feature;

use App\DeletedUsername;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_register_successfully()
    {
        $response = $this->post('/register', [
            'username' => 'johndoe',
            'email' => 'johndoe@example.com',
            'email_confirmation' => 'johndoe@example.com',
            'password' => 'mypassword',
            'terms' => true,
        ]);

        $response
            ->assertRedirect('/')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'username' => 'johndoe'
        ]);
    }

    /** @test */
    public function user_must_use_valid_username()
    {
        $response = $this->post('/register', [
            'username' => 'john_doe',
            'email' => 'johndoe@example.com',
            'password' => 'mypassword',
            'terms' => true,
        ]);

        $response->assertSessionHasErrors(['username']);

        $this->assertDatabaseMissing('users', [
            'username' => 'john_doe'
        ]);
    }

    /** @test */
    public function user_must_confirm_email()
    {
        $response = $this->post('/register', [
            'username' => 'johndoe',
            'email' => 'johndoe@example.com',
            'password' => 'mypassword',
            'terms' => true,
        ]);

        $response->assertSessionHasErrors(['email']);

        $this->assertDatabaseMissing('users', [
            'username' => 'johndoe'
        ]);
    }

    /** @test */
    public function user_must_accept_terms()
    {
        $response = $this->post('/register', [
            'username' => 'johndoe',
            'email' => 'johndoe@example.com',
            'email_confirmation' => 'johndoe@example.com',
            'password' => 'mypassword',
            'terms' => false,
        ]);

        $response->assertSessionHasErrors(['terms']);

        $this->assertDatabaseMissing('users', [
            'username' => 'johndoe'
        ]);
    }

    /** @test */
    public function user_cannot_register_with_existing_username()
    {
        factory(User::class)->create(['username' => 'johndoe']);

        $response = $this->post('/register', [
            'username' => 'johndoe',
            'email' => 'johndoe@example.com',
            'email_confirmation' => 'johndoe@example.com',
            'password' => 'mypassword',
            'terms' => true,
        ]);

        $response->assertSessionHasErrors(['username']);
    }

    /** @test */
    public function user_cannot_register_with_blacklisted_username()
    {
        $response = $this->post('/register', [
            'username' => 'www',
            'email' => 'johndoe@example.com',
            'email_confirmation' => 'johndoe@example.com',
            'password' => 'mypassword',
            'terms' => true,
        ]);

        $response->assertSessionHasErrors(['username']);

        $this->assertDatabaseMissing('users', [
            'username' => 'www'
        ]);
    }

    /** @test */
    public function user_cannot_register_with_uppercase_blacklisted_username()
    {
        $response = $this->post('/register', [
            'username' => 'Www',
            'email' => 'johndoe@example.com',
            'email_confirmation' => 'johndoe@example.com',
            'password' => 'mypassword',
            'terms' => true,
        ]);

        $response->assertSessionHasErrors(['username']);

        $this->assertDatabaseMissing('users', [
            'username' => 'www'
        ]);
    }

    /** @test */
    public function user_cannot_register_with_deleted_username()
    {
        DeletedUsername::create(['username' => 'johndoe']);

        $response = $this->post('/register', [
            'username' => 'johndoe',
            'email' => 'johndoe@example.com',
            'email_confirmation' => 'johndoe@example.com',
            'password' => 'mypassword',
            'terms' => true,
        ]);

        $response->assertSessionHasErrors(['username']);

        $this->assertDatabaseMissing('users', [
            'username' => 'johndoe'
        ]);
    }

    /** @test */
    public function user_cannot_register_with_uppercase_deleted_username()
    {
        DeletedUsername::create(['username' => 'johndoe']);

        $response = $this->post('/register', [
            'username' => 'joHndoe',
            'email' => 'johndoe@example.com',
            'email_confirmation' => 'johndoe@example.com',
            'password' => 'mypassword',
            'terms' => true,
        ]);

        $response->assertSessionHasErrors(['username']);

        $this->assertDatabaseMissing('users', [
            'username' => 'johndoe'
        ]);
    }
}
