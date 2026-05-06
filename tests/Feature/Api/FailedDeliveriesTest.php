<?php

namespace Tests\Feature\Api;

use App\Models\FailedDelivery;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FailedDeliveriesTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        parent::setUpSanctum();

        $this->user->recipients()->save($this->user->defaultRecipient);
    }

    #[Test]
    public function user_can_get_all_failed_deliveries()
    {
        // Arrange
        FailedDelivery::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        // Act
        $response = $this->json('GET', '/api/v1/failed-deliveries');

        // Assert
        $response->assertSuccessful();
        $this->assertCount(3, $response->json()['data']);
    }

    #[Test]
    public function user_can_get_individual_failed_delivery()
    {
        // Arrange
        $failedDelivery = FailedDelivery::factory()->create([
            'user_id' => $this->user->id,
        ]);

        // Act
        $response = $this->json('GET', '/api/v1/failed-deliveries/'.$failedDelivery->id);

        // Assert
        $response->assertSuccessful();
        $this->assertCount(1, $response->json());
        $this->assertEquals($failedDelivery->code, $response->json()['data']['code']);
    }

    #[Test]
    public function failed_delivery_resource_includes_normalised_type()
    {
        $inboundRuleFailedDelivery = FailedDelivery::factory()->create([
            'user_id' => $this->user->id,
            'email_type' => 'F',
            'ir_dedupe_key' => str_repeat('a', 64),
            'quarantined' => false,
        ]);
        $inboundQuarantinedFailedDelivery = FailedDelivery::factory()->create([
            'user_id' => $this->user->id,
            'email_type' => 'F',
            'quarantined' => true,
        ]);
        $inboundRejectionFailedDelivery = FailedDelivery::factory()->create([
            'user_id' => $this->user->id,
            'email_type' => 'IR',
            'quarantined' => false,
        ]);
        $outboundFailedDelivery = FailedDelivery::factory()->create([
            'user_id' => $this->user->id,
            'email_type' => 'F',
            'quarantined' => false,
        ]);

        $this->json('GET', '/api/v1/failed-deliveries/'.$inboundRuleFailedDelivery->id)
            ->assertSuccessful()
            ->assertJsonPath('data.type', 'inbound');

        $this->json('GET', '/api/v1/failed-deliveries/'.$inboundQuarantinedFailedDelivery->id)
            ->assertSuccessful()
            ->assertJsonPath('data.type', 'inbound_quarantined');

        $this->json('GET', '/api/v1/failed-deliveries/'.$inboundRejectionFailedDelivery->id)
            ->assertSuccessful()
            ->assertJsonPath('data.type', 'inbound');

        $this->json('GET', '/api/v1/failed-deliveries/'.$outboundFailedDelivery->id)
            ->assertSuccessful()
            ->assertJsonPath('data.type', 'outbound');
    }

    #[Test]
    public function user_can_filter_failed_deliveries_by_inbound_type()
    {
        FailedDelivery::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'email_type' => 'IR',
        ]);
        FailedDelivery::factory()->create([
            'user_id' => $this->user->id,
            'email_type' => 'F',
        ]);

        $response = $this->json('GET', '/api/v1/failed-deliveries?filter[email_type]=inbound');

        $response->assertSuccessful();
        $this->assertCount(2, $response->json()['data']);
    }

    #[Test]
    public function user_can_filter_failed_deliveries_by_outbound_type()
    {
        FailedDelivery::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'email_type' => 'IR',
        ]);
        FailedDelivery::factory()->create([
            'user_id' => $this->user->id,
            'email_type' => 'F',
        ]);

        $response = $this->json('GET', '/api/v1/failed-deliveries?filter[email_type]=outbound');

        $response->assertSuccessful();
        $this->assertCount(1, $response->json()['data']);
    }

    #[Test]
    public function user_can_filter_failed_deliveries_by_quarantined_type()
    {
        FailedDelivery::factory()->create([
            'user_id' => $this->user->id,
            'email_type' => 'F',
            'quarantined' => true,
        ]);
        FailedDelivery::factory()->create([
            'user_id' => $this->user->id,
            'email_type' => 'F',
            'quarantined' => false,
        ]);

        $response = $this->json('GET', '/api/v1/failed-deliveries?filter[email_type]=inbound_quarantined');

        $response->assertSuccessful();
        $this->assertCount(1, $response->json()['data']);
    }

    #[Test]
    public function user_can_paginate_failed_deliveries()
    {
        FailedDelivery::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->json('GET', '/api/v1/failed-deliveries?page[size]=2&page[number]=1');

        $response->assertSuccessful();
        $this->assertCount(2, $response->json()['data']);
        $this->assertEquals(3, $response->json()['meta']['total']);
    }

    #[Test]
    public function user_can_delete_failed_delivery()
    {
        $failedDelivery = FailedDelivery::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->json('DELETE', '/api/v1/failed-deliveries/'.$failedDelivery->id);

        $response->assertStatus(204);
        $this->assertEmpty($this->user->failedDeliveries);
    }
}
