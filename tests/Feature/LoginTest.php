<?php

namespace Tests\Feature;

use App\Notifications\UsernameReminder;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
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

    /** @test */
    public function user_can_login_successfully_using_backup_code()
    {
        $this->user->update([
            'two_factor_enabled' => true,
            'two_factor_secret' => 'secret',
            'two_factor_backup_code' => bcrypt($code = Str::random(40))
        ]);

        $response = $this->post('/login', [
            'username' => 'johndoe',
            'password' => 'mypassword'
        ]);

        $response
            ->assertRedirect('/')
            ->assertSessionHasNoErrors();

        $secondFactor = $this->get('/recipients');

        $secondFactor->assertSee('2nd Factor Authentication');

        $backupCodeView = $this->get('/login/backup-code');

        $backupCodeView->assertSee('Login Using 2FA Backup Code');

        $backupCodeLogin = $this->post('/login/backup-code', [
            'backup_code' => $code
        ]);

        $backupCodeLogin
            ->assertRedirect('/recipients')
            ->assertSessionHasNoErrors();
    }

    /** @test */
    public function user_can_receive_username_reminder_email()
    {
        Notification::fake();

        $this->post('/username/email', [
            'email' => $this->user->email
        ]);

        Notification::assertSentTo(
            $this->user,
            UsernameReminder::class
        );
    }

    /** @test */
    public function username_reminder_email_not_sent_for_unkown_email()
    {
        Notification::fake();

        $this->post('/username/email', [
            'email' => 'doesnotexist@example.com'
        ]);

        Notification::assertNothingSent();
    }
}
