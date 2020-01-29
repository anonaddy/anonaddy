<?php

namespace Tests\Feature;

use App\AdditionalUsername;
use App\DeletedUsername;
use App\Recipient;
use App\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_register_successfully()
    {
        Notification::fake();

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

        $user = User::where('username', 'johndoe')->first();

        Notification::assertSentTo(
            $user,
            VerifyEmail::class
        );
    }

    /** @test */
    public function user_cannot_register_with_invalid_characters()
    {
        Notification::fake();

        $response = $this->post('/register', [
            'username' => 'Ω',
            'email' => 'johndoe@example.com',
            'email_confirmation' => 'johndoe@example.com',
            'password' => 'mypassword',
            'terms' => true,
        ]);

        $response->assertSessionHasErrors(['username']);

        $this->assertDatabaseMissing('users', [
            'username' => 'Ω'
        ]);
    }

    /** @test */
    public function user_can_verify_email_successfully()
    {
        $user = factory(User::class)->create();
        $user->email_verified_at = null;
        $user->save();

        $this->assertNull($user->refresh()->email_verified_at);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        $response
            ->assertRedirect('/')
            ->assertSessionHas('verified');

        $this->assertNotNull($user->refresh()->email_verified_at);
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
    public function user_cannot_register_with_existing_email()
    {
        $user = factory(User::class)->create(['username' => 'johndoe']);

        factory(Recipient::class)->create([
            'user_id' => $user->id,
            'email' => 'johndoe@example.com'
        ]);

        $response = $this->post('/register', [
            'username' => 'johndoe',
            'email' => 'johndoe@example.com',
            'email_confirmation' => 'johndoe@example.com',
            'password' => 'mypassword',
            'terms' => true,
        ]);

        $response->assertSessionHasErrors(['email']);
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
    public function user_cannot_register_with_existing_additional_username()
    {
        factory(AdditionalUsername::class)->create(['username' => 'johndoe']);

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
