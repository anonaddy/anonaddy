<?php

namespace Tests\Feature;

use App\Domain;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DomainsTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = factory(User::class)->create();
        $this->actingAs($this->user);
    }

    /** @test */
    public function user_can_view_domains_from_the_domains_page()
    {
        $domains = factory(Domain::class, 3)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->get('/domains');

        $response->assertSuccessful();
        $this->assertCount(3, $response->data('domains'));
        $domains->assertEquals($response->data('domains'));
    }

    /** @test */
    public function latest_domains_are_listed_first()
    {
        $a = factory(Domain::class)->create([
            'user_id' => $this->user->id,
            'created_at' => Carbon::now()->subDays(15)
        ]);
        $b = factory(Domain::class)->create([
            'user_id' => $this->user->id,
            'created_at' => Carbon::now()->subDays(5)
        ]);
        $c = factory(Domain::class)->create([
            'user_id' => $this->user->id,
            'created_at' => Carbon::now()->subDays(10)
        ]);

        $response = $this->get('/domains');

        $response->assertSuccessful();
        $this->assertCount(3, $response->data('domains'));
        $this->assertTrue($response->data('domains')[0]->is($b));
        $this->assertTrue($response->data('domains')[1]->is($c));
        $this->assertTrue($response->data('domains')[2]->is($a));
    }

    /** @test */
    public function user_can_verify_domain_sending_records()
    {
        $domain = factory(Domain::class)->create([
            'user_id' => $this->user->id,
            'domain' => 'example.com'
        ]);

        $response = $this->get('/domains/'.$domain->id.'/check-sending');

        $response->assertStatus(200);

        $this->assertDatabaseHas('domains', [
            'user_id' => $this->user->id,
            'domain' => 'example.com',
            'domain_sending_verified_at' => $response->json('data')['domain_sending_verified_at']
        ]);
    }
}
