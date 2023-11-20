<?php

namespace Tests\Unit;

use App\Mail\TokenExpiringSoon;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailUsersWithTokenExpiringSoonTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        parent::setUpSanctum();
        $this->user->createToken('test', ['*'], Carbon::create(2019, 1, 31));

        Mail::fake();
    }

    /** @test */
    public function it_can_send_a_mail_concerning_a_token_expiring_soon()
    {
        $this->setNow(2019, 1, 28);
        $this->artisan('anonaddy:email-users-with-token-expiring-soon');
        Mail::assertNotQueued(TokenExpiringSoon::class);

        $this->setNow(2019, 1, 29);
        $this->artisan('anonaddy:email-users-with-token-expiring-soon');
        Mail::assertNotQueued(TokenExpiringSoon::class);

        $this->setNow(2019, 1, 24);
        $this->artisan('anonaddy:email-users-with-token-expiring-soon');
        Mail::assertQueued(TokenExpiringSoon::class, 1);
        Mail::assertQueued(TokenExpiringSoon::class, function (TokenExpiringSoon $mail) {
            return $mail->hasTo($this->user->email);
        });
    }

    protected function setNow(int $year, int $month, int $day)
    {
        $newNow = Carbon::create($year, $month, $day)->startOfDay();

        Carbon::setTestNow($newNow);

        return $this;
    }
}
