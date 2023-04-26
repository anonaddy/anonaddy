<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ShowDomainsTest extends TestCase
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
    public function user_can_view_domains_from_the_domains_page()
    {
        $domains = Domain::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->get('/domains');

        $response->assertSuccessful();
        $this->assertCount(3, $response->data('domains'));
        $domains->assertEquals($response->data('domains'));
    }

    /** @test */
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
        $this->assertCount(3, $response->data('domains'));
        $this->assertTrue($response->data('domains')[0]->is($b));
        $this->assertTrue($response->data('domains')[1]->is($c));
        $this->assertTrue($response->data('domains')[2]->is($a));
    }

    /** @test */
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
