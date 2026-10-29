<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Support\Facades\Password;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ResetPasswordTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[Test]
    public function resetting_the_password_revokes_api_keys_and_tells_the_user(): void
    {
        $this->withoutMiddleware(ThrottleRequestsWithRedis::class);

        $user = $this->createUser('resetuser');
        $user->createToken('Browser Extension');

        $token = Password::broker()->createToken($user);

        $this->post('/password/reset', [
            'token' => $token,
            'username' => 'resetuser',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertSessionHasNoErrors()
            ->assertSessionHas('flash', trans('passwords.reset'));

        $this->assertSame(0, $user->tokens()->count());
    }
}
