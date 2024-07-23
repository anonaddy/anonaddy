<?php

namespace Tests\Feature\Api;

use App\Models\Domain;
use App\Models\Recipient;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DomainsTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        parent::setUpSanctum();
    }

    #[Test]
    public function user_can_get_all_domains()
    {
        // Arrange
        Domain::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        // Act
        $response = $this->json('GET', '/api/v1/domains');

        // Assert
        $response->assertSuccessful();
        $this->assertCount(3, $response->json()['data']);
    }

    #[Test]
    public function user_can_get_individual_domain()
    {
        // Arrange
        $domain = Domain::factory()->create([
            'user_id' => $this->user->id,
        ]);

        // Act
        $response = $this->json('GET', '/api/v1/domains/'.$domain->id);

        // Assert
        $response->assertSuccessful();
        $this->assertCount(1, $response->json());
        $this->assertEquals($domain->domain, $response->json()['data']['domain']);
    }

    #[Test]
    public function user_can_create_new_domain()
    {
        $response = $this->json('POST', '/api/v1/domains', [
            'domain' => 'random.com',
        ]);

        $response->assertStatus(201);
        $this->assertEquals('random.com', $response->getData()->data->domain);
    }

    #[Test]
    public function user_can_not_create_the_same_domain()
    {
        Domain::factory()->create([
            'user_id' => $this->user->id,
            'domain' => 'random.com',
        ]);

        $response = $this->json('POST', '/api/v1/domains', [
            'domain' => 'random.com',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('domain');
    }

    #[Test]
    public function new_domain_must_be_a_valid_fqdn()
    {
        $response = $this->json('POST', '/api/v1/domains', [
            'domain' => 'random.',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('domain');
    }

    #[Test]
    public function new_domain_must_not_include_protocol()
    {
        $response = $this->json('POST', '/api/v1/domains', [
            'domain' => 'https://random.com',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('domain');
    }

    #[Test]
    public function new_domain_must_not_be_local()
    {
        $response = $this->json('POST', '/api/v1/domains', [
            'domain' => config('anonaddy.domain'),
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('domain');
    }

    #[Test]
    public function new_domain_must_not_be_local_subdomain()
    {
        $response = $this->json('POST', '/api/v1/domains', [
            'domain' => 'subdomain.'.config('anonaddy.domain'),
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('domain');
    }

    #[Test]
    public function user_can_activate_domain()
    {
        $domain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'active' => false,
        ]);

        $response = $this->json('POST', '/api/v1/active-domains/', [
            'id' => $domain->id,
        ]);

        $response->assertStatus(200);
        $this->assertEquals(true, $response->getData()->data->active);
    }

    #[Test]
    public function user_can_deactivate_domain()
    {
        $domain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'active' => true,
        ]);

        $response = $this->json('DELETE', '/api/v1/active-domains/'.$domain->id);

        $response->assertStatus(204);
        $this->assertFalse($this->user->domains[0]->active);
    }

    #[Test]
    public function user_can_enable_catch_all_for_domain()
    {
        $domain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'catch_all' => false,
        ]);

        $response = $this->json('POST', '/api/v1/catch-all-domains/', [
            'id' => $domain->id,
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->getData()->data->catch_all);
    }

    #[Test]
    public function user_can_disable_catch_all_for_domain()
    {
        $domain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'catch_all' => true,
        ]);

        $response = $this->json('DELETE', '/api/v1/catch-all-domains/'.$domain->id);

        $response->assertStatus(204);
        $this->assertFalse($this->user->domains[0]->catch_all);
    }

    #[Test]
    public function user_can_update_domain_description()
    {
        $domain = Domain::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->json('PATCH', '/api/v1/domains/'.$domain->id, [
            'description' => 'The new description',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('The new description', $response->getData()->data->description);
    }

    #[Test]
    public function user_can_update_domain_from_name()
    {
        $domain = Domain::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->json('PATCH', '/api/v1/domains/'.$domain->id, [
            'from_name' => 'John Doe',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('John Doe', $response->getData()->data->from_name);
    }

    #[Test]
    public function user_can_update_domain_auto_create_regex()
    {
        $domain = Domain::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->json('PATCH', '/api/v1/domains/'.$domain->id, [
            'auto_create_regex' => '^prefix',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('^prefix', $response->getData()->data->auto_create_regex);
    }

    #[Test]
    public function domain_auto_create_regex_must_be_valid()
    {
        $domain = Domain::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->json('PATCH', '/api/v1/domains/'.$domain->id, [
            'auto_create_regex' => '///',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('auto_create_regex');
    }

    #[Test]
    public function user_can_delete_domain()
    {
        $domain = Domain::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->json('DELETE', '/api/v1/domains/'.$domain->id);

        $response->assertStatus(204);
        $this->assertEmpty($this->user->domains);
    }

    #[Test]
    public function user_can_update_domain_default_recipient()
    {
        $domain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'domain_verified_at' => now(),
        ]);

        $newDefaultRecipient = Recipient::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->json('PATCH', '/api/v1/domains/'.$domain->id.'/default-recipient', [
            'default_recipient' => $newDefaultRecipient->id,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('domains', [
            'id' => $domain->id,
            'default_recipient_id' => $newDefaultRecipient->id,
        ]);

        $this->assertEquals($newDefaultRecipient->email, $domain->refresh()->defaultRecipient->email);
    }

    #[Test]
    public function user_cannot_update_domain_default_recipient_with_unverified_recipient()
    {
        $domain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'domain_verified_at' => now(),
        ]);

        $newDefaultRecipient = Recipient::factory()->create([
            'user_id' => $this->user->id,
            'email_verified_at' => null,
        ]);

        $response = $this->json('PATCH', '/api/v1/domains/'.$domain->id.'/default-recipient', [
            'default_recipient' => $newDefaultRecipient->id,
        ]);

        $response->assertStatus(404);
        $this->assertDatabaseMissing('domains', [
            'id' => $domain->id,
            'default_recipient_id' => $newDefaultRecipient->id,
        ]);
    }

    #[Test]
    public function user_can_remove_domain_default_recipient()
    {
        $defaultRecipient = Recipient::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $domain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'default_recipient_id' => $defaultRecipient->id,
            'domain_verified_at' => now(),
        ]);

        $response = $this->json('PATCH', '/api/v1/domains/'.$domain->id.'/default-recipient', [
            'default_recipient' => '',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('domains', [
            'id' => $domain->id,
            'default_recipient_id' => null,
        ]);

        $this->assertNull($domain->refresh()->defaultRecipient);
    }
}
