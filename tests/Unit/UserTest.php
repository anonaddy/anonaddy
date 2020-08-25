<?php

namespace Tests\Unit;

use App\Alias;
use App\AliasRecipient;
use App\Recipient;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = factory(User::class)->create();
        $this->user->recipients()->save($this->user->defaultRecipient);
    }

    /** @test */
    public function user_can_get_aliases_from_relationship()
    {
        $aliases = factory(Alias::class, 10)->create([
            'user_id' => $this->user->id
        ]);

        $aliases->assertEquals($this->user->aliases);
    }

    /** @test */
    public function user_can_only_get_their_own_aliases_from_relationship()
    {
        $aliases = factory(Alias::class, 5)->create([
            'user_id' => $this->user->id
        ]);

        factory(Alias::class, 10)->create();

        $aliases->assertEquals($this->user->aliases);
    }

    /** @test */
    public function user_can_get_total_emails_forwarded()
    {
        factory(Alias::class)->create([
            'user_id' => $this->user->id,
            'emails_forwarded' => 5
        ]);

        factory(Alias::class)->create([
            'user_id' => $this->user->id,
            'emails_forwarded' => 3,
            'deleted_at' => now()
        ]);

        factory(Alias::class)->create([
            'user_id' => $this->user->id,
            'emails_forwarded' => 2,
            'active' => false
        ]);

        $this->assertEquals(10, $this->user->totalEmailsForwarded());
    }

    /** @test */
    public function user_can_get_total_emails_blocked()
    {
        factory(Alias::class)->create([
            'user_id' => $this->user->id,
            'emails_blocked' => 3
        ]);

        factory(Alias::class)->create([
            'user_id' => $this->user->id,
            'emails_blocked' => 4,
            'deleted_at' => now()
        ]);

        factory(Alias::class)->create([
            'user_id' => $this->user->id,
            'emails_blocked' => 1,
            'active' => false
        ]);

        $this->assertEquals(8, $this->user->totalEmailsBlocked());
    }

    /** @test */
    public function user_can_get_total_emails_replied()
    {
        factory(Alias::class)->create([
            'user_id' => $this->user->id,
            'emails_replied' => 2
        ]);

        factory(Alias::class)->create([
            'user_id' => $this->user->id,
            'emails_replied' => 4,
            'deleted_at' => now()
        ]);

        factory(Alias::class)->create([
            'user_id' => $this->user->id,
            'emails_replied' => 1,
            'active' => false
        ]);

        $this->assertEquals(7, $this->user->totalEmailsReplied());
    }

    /** @test */
    public function user_can_get_aliases_using_default_recipient()
    {
        $recipient = factory(Recipient::class)->create([
            'user_id' => $this->user->id
        ]);

        $aliasNotUsingDefault = factory(Alias::class)->create([
            'user_id' => $this->user->id
        ]);

        AliasRecipient::create([
            'alias' => $aliasNotUsingDefault,
            'recipient' => $recipient
        ]);

        factory(Alias::class)->create([
            'user_id' => $this->user->id
        ]);

        factory(Alias::class)->create([
            'user_id' => $this->user->id,
            'deleted_at' => now()
        ]);

        factory(Alias::class)->create([
            'user_id' => $this->user->id,
            'active' => false
        ]);

        $this->assertCount(2, $this->user->aliasesUsingDefault);
        $this->assertCount(3, $this->user->aliases);
    }

    /** @test */
    public function user_can_get_bandwidth_in_mb()
    {
        $this->user->update(['bandwidth' => 10485760]);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'bandwidth' => 10485760
        ]);

        $this->assertEquals(10, $this->user->bandwidth_mb);
    }

    /** @test */
    public function user_can_get_bandwidth_in_mb_to_correct_precision()
    {
        $this->user->update(['bandwidth' => 7324019]);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'bandwidth' => 7324019
        ]);

        $this->assertEquals(6.98, $this->user->bandwidth_mb);
    }

    /** @test */
    public function user_can_get_bandwidth_limit_in_mb()
    {
        $this->assertEquals(100, $this->user->getBandwidthLimitMb());
    }

    /** @test */
    public function user_can_check_if_near_bandwidth_usage_limit()
    {
        $this->user->update(['bandwidth' => 100943820]);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'bandwidth' => 100943820
        ]);

        $this->assertTrue($this->user->nearBandwidthLimit());

        $this->assertEquals(96.27, $this->user->bandwidth_mb);
    }

    /** @test */
    public function user_get_domain_options()
    {
        $username = $this->user->username;

        $domainOptions = $this->user->domainOptions();

        $expected = collect([
            'anonaddy.me',
            'anonaddy.com',
            $username.'.anonaddy.me',
            $username.'.anonaddy.com',
        ]);

        $this->assertCount($expected->count(), $domainOptions);

        $expected->zip($domainOptions)->each(function ($itemPair) {
            $this->assertEquals($itemPair[0], $itemPair[1]);
        });
    }

    /** @test */
    public function user_can_match_verified_recipient_with_extension()
    {
        $this->user->defaultRecipient->email = 'hello+anonaddy@example.com';
        $this->user->defaultRecipient->save();
        $this->assertTrue($this->user->isVerifiedRecipient('hello@example.com'));

        $this->user->defaultRecipient->email = 'hello+anonaddy+another@example.net';
        $this->user->defaultRecipient->save();
        $this->assertTrue($this->user->isVerifiedRecipient('hello@example.net'));

        $this->user->defaultRecipient->email = 'hello+anonaddy@example.net';
        $this->user->defaultRecipient->save();
        $this->assertTrue($this->user->isVerifiedRecipient('hello+anonaddy@example.net'));
    }
}
