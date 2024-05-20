<?php

namespace Tests\Feature;

use App\Models\Recipient;
use App\Notifications\CustomVerifyEmail;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShowRecipientsTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUser();
        $this->actingAs($this->user);
    }

    #[Test]
    public function user_can_view_recipients_from_the_recipients_page()
    {
        Recipient::factory()->count(5)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->get('/recipients');

        $response->assertSuccessful();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('initialRows', 6, fn (Assert $page) => $page
                ->where('user_id', $this->user->id)
                ->etc()
            )
        );
    }

    #[Test]
    public function latest_recipients_are_listed_first()
    {
        $a = Recipient::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => Carbon::now()->subDays(15),
        ]);
        $b = Recipient::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => Carbon::now()->subDays(5),
        ]);
        $c = Recipient::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => Carbon::now()->subDays(10),
        ]);

        $response = $this->get('/recipients');

        $response->assertSuccessful();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('initialRows', 4, fn (Assert $page) => $page
                ->where('user_id', $this->user->id)
                ->etc()
            )
        );
        $this->assertTrue($response->data('page')['props']['initialRows'][1]['id'] === $b->id);
        $this->assertTrue($response->data('page')['props']['initialRows'][2]['id'] === $c->id);
        $this->assertTrue($response->data('page')['props']['initialRows'][3]['id'] === $a->id);
    }

    /* #[Test]
    public function recipients_are_listed_with_aliases_count()
    {
        $recipient = Recipient::factory()->create([
            'user_id' => $this->user->id,
        ]);

        Alias::factory()->count(3)->create(['user_id' => $this->user->id])
            ->each(function ($alias) use ($recipient) {
                AliasRecipient::create([
                    'alias' => $alias,
                    'recipient' => $recipient,
                ]);
            });

        $response = $this->get('/recipients');

        $response->assertSuccessful();
        $this->assertCount(1, $response->data('recipients'));
        $this->assertCount(3, $response->data('recipients')[0]['aliases']);
    } */

    #[Test]
    public function user_can_resend_recipient_verification_email()
    {
        Notification::fake();

        Notification::assertNothingSent();

        $recipient = Recipient::factory()->create([
            'user_id' => $this->user->id,
            'email_verified_at' => null,
        ]);

        $response = $this->json('POST', '/api/v1/recipients/email/resend', [
            'recipient_id' => $recipient->id,
        ]);

        $response->assertStatus(200);

        Notification::assertSentTo(
            $recipient,
            CustomVerifyEmail::class
        );
    }

    #[Test]
    public function user_can_verify_recipient_email_successfully()
    {
        $recipient = Recipient::factory()->create([
            'user_id' => $this->user->id,
            'email_verified_at' => null,
        ]);

        $this->assertNull($recipient->refresh()->email_verified_at);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $recipient->getKey(),
                'hash' => base64_encode(Hash::make($recipient->getEmailForVerification())),
            ]
        );

        $response = $this->get($verificationUrl);

        $response
            ->assertRedirect('/recipients')
            ->assertSessionHas('verified');

        $this->assertNotNull($recipient->refresh()->email_verified_at);
    }

    #[Test]
    public function user_must_wait_before_resending_recipient_verification_email()
    {
        Notification::fake();

        Notification::assertNothingSent();

        $recipient = Recipient::factory()->create([
            'user_id' => $this->user->id,
            'email_verified_at' => null,
        ]);

        $response = $this->json('POST', '/api/v1/recipients/email/resend', [
            'recipient_id' => $recipient->id,
        ]);

        $response->assertStatus(200);

        Notification::assertSentTo(
            $recipient,
            CustomVerifyEmail::class
        );

        $response2 = $this->json('POST', '/api/v1/recipients/email/resend', [
            'recipient_id' => $recipient->id,
        ]);

        $response2->assertStatus(429);
    }
}
