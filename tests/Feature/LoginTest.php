<?php

namespace Tests\Feature;

use App\Enums\LoginRedirect;
use App\Models\Username;
use App\Notifications\UsernameReminder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUser('johndoe', null, ['password' => Hash::make('mypassword')]);
    }

    /** @test */
    public function user_can_login_successfully()
    {
        $response = $this->post('/login', [
            'username' => 'johndoe',
            'password' => 'mypassword',
        ]);

        $response
            ->assertRedirect('/')
            ->assertSessionHasNoErrors();
    }

    /** @test */
    public function user_can_login_and_be_redirected_based_on_login_redirect_successfully()
    {
        $this->withoutMiddleware(ThrottleRequestsWithRedis::class);

        $this->user->login_redirect = LoginRedirect::ALIASES;
        $this->user->save();

        $response = $this->post('/login', [
            'username' => 'johndoe',
            'password' => 'mypassword',
        ]);

        $response
            ->assertRedirect('/aliases')
            ->assertSessionHasNoErrors();
    }

    /** @test */
    public function user_can_login_with_any_username()
    {
        $username = Username::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->post('/login', [
            'username' => $username->username,
            'password' => 'mypassword',
        ]);

        $response
            ->assertRedirect('/')
            ->assertSessionHasNoErrors();
    }

    /** @test */
    public function user_can_login_successfully_using_backup_code()
    {
        $this->user->update([
            'two_factor_enabled' => true,
            'two_factor_secret' => 'secret',
            'two_factor_backup_code' => bcrypt($code = Str::random(40)),
        ]);

        $response = $this->post('/login', [
            'username' => 'johndoe',
            'password' => 'mypassword',
        ]);

        $response
            ->assertRedirect('/')
            ->assertSessionHasNoErrors();

        $secondFactor = $this->get('/recipients');

        $secondFactor->assertSee('2nd Factor Authentication');

        $backupCodeView = $this->get('/login/backup-code');

        $backupCodeView->assertSee('Login Using 2FA Backup Code');

        $backupCodeLogin = $this->post('/login/backup-code', [
            'backup_code' => $code,
        ]);

        $backupCodeLogin
            ->assertRedirect('/recipients')
            ->assertSessionHasNoErrors();
    }

    /** @test */
    public function user_can_receive_username_reminder_email()
    {
        $this->withoutMiddleware();

        Notification::fake();

        $recipient = $this->user->recipients[0];

        $this->post('/username/email', [
            'email' => $recipient->email,
        ]);

        Notification::assertSentTo(
            $recipient,
            UsernameReminder::class
        );
    }

    /** @test */
    public function username_reminder_email_not_sent_for_unkown_email()
    {
        $this->withoutMiddleware();

        Notification::fake();

        $this->post('/username/email', [
            'email' => 'doesnotexist@example.com',
        ]);

        Notification::assertNothingSent();
    }
}
