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
