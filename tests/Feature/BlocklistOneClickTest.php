<?php

namespace Tests\Feature;

use App\Models\Alias;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BlocklistOneClickTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected Alias $alias;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUser();
        $this->alias = Alias::factory()->create(['user_id' => $this->user->id]);
    }

    #[Test]
    public function one_click_block_email_only_blocks_the_signed_email(): void
    {
        $url = URL::temporarySignedRoute('block_email_post', now()->addDays(30), ['alias' => $this->alias->id, 'email' => 'sender@example.com']);

        $this->post($url, ['email' => 'friend@example.com', 'List-Unsubscribe' => 'One-Click'])->assertOk();

        $this->assertSame(['sender@example.com'], $this->user->blockedSenders()->pluck('value')->all());
    }

    #[Test]
    public function one_click_block_domain_only_blocks_the_signed_domain(): void
    {
        $url = URL::temporarySignedRoute('block_domain_post', now()->addDays(30), ['alias' => $this->alias->id, 'domain' => 'example.com']);

        $this->post($url, ['domain' => 'gmail.com', 'List-Unsubscribe' => 'One-Click'])->assertOk();

        $this->assertSame(['example.com'], $this->user->blockedSenders()->pluck('value')->all());
    }

    #[Test]
    public function one_click_block_domain_cannot_block_an_alias_domain(): void
    {
        $url = URL::temporarySignedRoute('block_domain_post', now()->addDays(30), ['alias' => $this->alias->id, 'domain' => 'anonaddy.me']);

        $this->post($url, ['List-Unsubscribe' => 'One-Click'])->assertUnprocessable();

        $this->assertSame(0, $this->user->blockedSenders()->count());
    }
}
