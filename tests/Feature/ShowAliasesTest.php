<?php

namespace Tests\Feature;

use App\Models\Alias;
use App\Models\AliasRecipient;
use App\Models\Recipient;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ShowAliasesTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create()->fresh();
        $this->actingAs($this->user);
    }

    /** @test */
    public function user_can_view_aliases_from_the_dashboard()
    {
        // Arrange
        $aliases = Alias::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        // Act
        $response = $this->get('/');

        // Assert
        $response->assertSuccessful();
        $this->assertCount(3, $response->data('aliases'));
        $aliases->assertEquals($response->data('aliases'));
    }

    /** @test */
    public function latest_aliases_are_listed_first()
    {
        // Arrange
        $a = Alias::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => Carbon::now()->subDays(15),
        ]);
        $b = Alias::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => Carbon::now()->subDays(5),
        ]);
        $c = Alias::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => Carbon::now()->subDays(10),
        ]);

        // Act
        $response = $this->get('/');

        // Assert
        $response->assertSuccessful();
        $this->assertCount(3, $response->data('aliases'));
        $this->assertTrue($response->data('aliases')[0]->is($b));
        $this->assertTrue($response->data('aliases')[1]->is($c));
        $this->assertTrue($response->data('aliases')[2]->is($a));
    }

    /** @test */
    public function deleted_aliases_are_not_listed()
    {
        Alias::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        Alias::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'deleted_at' => Carbon::now()->subDays(5),
        ]);

        $response = $this->get('/');

        $response->assertSuccessful();
        $this->assertCount(3, $response->data('aliases'));
    }

    /** @test */
    public function aliases_are_listed_with_recipients()
    {
        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $recipient = Recipient::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $aliasRecipient = AliasRecipient::create([
            'alias' => $alias,
            'recipient' => $recipient,
        ]);

        $response = $this->get('/');

        $response->assertSuccessful();
        $this->assertCount(1, $response->data('aliases'));
        $this->assertEquals($aliasRecipient->recipient->email, $response->data('aliases')[0]['recipients'][0]['email']);
    }

    /** @test */
    public function aliases_are_listed_with_only_verified_recipient_options()
    {
        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $recipient = Recipient::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $unverifiedRecipient = Recipient::factory()->create([
            'user_id' => $this->user->id,
            'email_verified_at' => null,
        ]);

        $aliasRecipient = AliasRecipient::create([
            'alias' => $alias,
            'recipient' => $recipient,
        ]);

        AliasRecipient::create([
            'alias' => $alias,
            'recipient' => $unverifiedRecipient,
        ]);

        $response = $this->get('/');

        $response->assertSuccessful();
        $this->assertCount(1, $response->data('recipients'));
        $this->assertEquals($aliasRecipient->recipient->email, $response->data('recipients')[0]['email']);
    }
}
