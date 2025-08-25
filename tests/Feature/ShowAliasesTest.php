<?php

namespace Tests\Feature;

use App\Models\Alias;
use App\Models\AliasRecipient;
use App\Models\Recipient;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShowAliasesTest extends TestCase
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
    public function user_can_view_aliases_from_the_dashboard()
    {
        // Arrange
        Alias::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        // Act
        $response = $this->get('/aliases');

        // Assert
        $response->assertSuccessful();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('initialRows.data', 3, fn (Assert $page) => $page
                ->where('user_id', $this->user->id)
                ->etc()
            )
        );
    }

    #[Test]
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
        $response = $this->get('/aliases');

        // Assert
        $response->assertSuccessful();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('initialRows.data', 3, fn (Assert $page) => $page
                ->where('user_id', $this->user->id)
                ->etc()
            )
        );
        $this->assertTrue($response->data('page')['props']['initialRows']['data'][0]['id'] === $b->id);
        $this->assertTrue($response->data('page')['props']['initialRows']['data'][1]['id'] === $c->id);
        $this->assertTrue($response->data('page')['props']['initialRows']['data'][2]['id'] === $a->id);
    }

    #[Test]
    public function deleted_aliases_are_not_listed()
    {
        Alias::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        Alias::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'deleted_at' => Carbon::now()->subDays(5),
        ]);

        $response = $this->get('/aliases');

        $response->assertSuccessful();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('initialRows.data', 3, fn (Assert $page) => $page
                ->where('user_id', $this->user->id)
                ->etc()
            )
        );
    }

    #[Test]
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

        $response = $this->get('/aliases');

        $response->assertSuccessful();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('initialRows.data', 1, fn (Assert $page) => $page
                ->where('user_id', $this->user->id)
                ->etc()
            )
        );
        $this->assertEquals($aliasRecipient->recipient->email, $response->data('page')['props']['initialRows']['data'][0]['recipients'][0]['email']);
    }

    #[Test]
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

        $response = $this->get('/aliases');

        $response->assertSuccessful();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('recipientOptions', 2)
        );
        $this->assertEquals($aliasRecipient->recipient->email, $response->data('page')['props']['recipientOptions'][1]['email']);
    }
}
