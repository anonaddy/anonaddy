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
    public function user_can_create_new_domain()
    {
        $response = $this->json('POST', '/domains', [
            'domain' => 'example.com'
        ]);

        $response->assertStatus(200);
        $this->assertEquals('example.com', $response->getData()->data->domain);
    }

    /** @test */
    public function user_can_not_create_the_same_domain()
    {
        factory(Domain::class)->create([
            'user_id' => $this->user->id,
            'domain' => 'example.com'
        ]);

        $response = $this->json('POST', '/domains', [
            'domain' => 'example.com'
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('domain');
    }

    /** @test */
    public function new_domain_must_be_a_valid_fqdn()
    {
        $response = $this->json('POST', '/domains', [
            'domain' => 'example.'
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('domain');
    }

    /** @test */
    public function new_domain_must_not_include_protocol()
    {
        $response = $this->json('POST', '/domains', [
            'domain' => 'https://example.com'
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('domain');
    }

    /** @test */
    public function new_domain_must_not_be_local()
    {
        $response = $this->json('POST', '/domains', [
            'domain' => config('anonaddy.domain')
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('domain');
    }

    /** @test */
    public function new_domain_must_not_be_local_subdomain()
    {
        $response = $this->json('POST', '/domains', [
            'domain' => 'subdomain'.config('anonaddy.domain')
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('domain');
    }

    /** @test */
    public function user_can_verify_domain_records()
    {
        $domain = factory(Domain::class)->create([
            'user_id' => $this->user->id,
            'domain' => 'anonaddy.me'
        ]);

        $response = $this->get('/domains/'.$domain->id.'/recheck');

        $response->assertStatus(200);

        $this->assertDatabaseHas('domains', [
            'user_id' => $this->user->id,
            'domain' => 'anonaddy.me',
            'domain_verified_at' => now()
        ]);
    }

    /** @test */
    public function user_can_activate_domain()
    {
        $domain = factory(Domain::class)->create([
            'user_id' => $this->user->id,
            'active' => false
        ]);

        $response = $this->json('POST', '/active-domains/', [
            'id' => $domain->id
        ]);

        $response->assertStatus(200);
        $this->assertEquals(true, $response->getData()->data->active);
    }

    /** @test */
    public function user_can_deactivate_domain()
    {
        $domain = factory(Domain::class)->create([
            'user_id' => $this->user->id,
            'active' => true
        ]);

        $response = $this->json('DELETE', '/active-domains/'.$domain->id);

        $response->assertStatus(200);
        $this->assertEquals(false, $response->getData()->data->active);
    }

    /** @test */
    public function user_can_update_domain_description()
    {
        $domain = factory(Domain::class)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->json('PATCH', '/domains/'.$domain->id, [
            'description' => 'The new description'
        ]);

        $response->assertStatus(200);
        $this->assertEquals('The new description', $response->getData()->data->description);
    }

    /** @test */
    public function user_can_delete_domain()
    {
        $domain = factory(Domain::class)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->json('DELETE', '/domains/'.$domain->id);

        $response->assertStatus(204);
        $this->assertEmpty($this->user->domains);
    }
}
