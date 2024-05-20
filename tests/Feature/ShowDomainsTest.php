<?php

namespace Tests\Feature;

use App\Models\Domain;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShowDomainsTest extends TestCase
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
    public function user_can_view_domains_from_the_domains_page()
    {
        Domain::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->get('/domains');

        $response->assertSuccessful();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('initialRows', 3, fn (Assert $page) => $page
                ->where('user_id', $this->user->id)
                ->etc()
            )
        );
    }

    #[Test]
    public function latest_domains_are_listed_first()
    {
        $a = Domain::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => Carbon::now()->subDays(15),
        ]);
        $b = Domain::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => Carbon::now()->subDays(5),
        ]);
        $c = Domain::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => Carbon::now()->subDays(10),
        ]);

        $response = $this->get('/domains');

        $response->assertSuccessful();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('initialRows', 3, fn (Assert $page) => $page
                ->where('user_id', $this->user->id)
                ->etc()
            )
        );
        $this->assertTrue($response->data('page')['props']['initialRows'][0]['id'] === $b->id);
        $this->assertTrue($response->data('page')['props']['initialRows'][1]['id'] === $c->id);
        $this->assertTrue($response->data('page')['props']['initialRows'][2]['id'] === $a->id);
    }

    #[Test]
    public function user_can_verify_domain_sending_records()
    {
        $domain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'domain' => 'example.com',
        ]);

        $response = $this->get('/domains/'.$domain->id.'/check-sending');

        $response->assertStatus(200);

        $this->assertEquals('Records verified for sending.', $response->json('message'));
    }
}
