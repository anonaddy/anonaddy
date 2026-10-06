<?php

namespace Tests\Feature\Api;

use App\Models\FailedDelivery;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChartDataControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        parent::setUpSanctum();
    }

    #[Test]
    public function it_counts_failed_deliveries_by_type_for_the_authenticated_user_in_the_last_7_days(): void
    {
        $this->travelTo('2026-09-04 12:00:00');

        $otherUser = $this->createUser();

        FailedDelivery::factory()->create([
            'user_id' => $this->user->id,
            'email_type' => 'IR',
            'quarantined' => false,
        ]);
        FailedDelivery::factory()->create([
            'user_id' => $this->user->id,
            'email_type' => 'F',
            'ir_dedupe_key' => hash('sha256', 'inbound-rule'),
            'quarantined' => false,
        ]);
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
        FailedDelivery::factory()->create([
            'user_id' => $this->user->id,
            'email_type' => 'R',
            'quarantined' => false,
            'created_at' => now()->subDay(),
        ]);
        FailedDelivery::factory()->create([
            'user_id' => $this->user->id,
            'email_type' => 'IR',
            'quarantined' => false,
            'created_at' => now()->subDays(8),
        ]);
        FailedDelivery::factory()->create([
            'user_id' => $otherUser->id,
            'email_type' => 'IR',
            'quarantined' => false,
        ]);

        $response = $this->json('GET', '/api/v1/chart-data');

        $response->assertSuccessful();
        $response->assertJsonPath('failedDeliveriesTotal', 5);

        $labels = array_values($response->json('labels'));
        $inboundRejections = array_values($response->json('inboundRejectionsData'));
        $inboundQuarantined = array_values($response->json('inboundQuarantinedData'));
        $outboundBounces = array_values($response->json('outboundBouncesData'));

        $this->assertSame(2, $inboundRejections[array_search('Friday', $labels, true)]);
        $this->assertSame(1, $inboundQuarantined[array_search('Friday', $labels, true)]);
        $this->assertSame(1, $outboundBounces[array_search('Friday', $labels, true)]);
        $this->assertSame(1, $outboundBounces[array_search('Thursday', $labels, true)]);
        $this->assertSame(0, $inboundRejections[array_search('Saturday', $labels, true)]);
    }

    #[Test]
    public function it_omits_intentional_failed_deliveries_from_the_chart_when_the_user_hides_them(): void
    {
        $this->travelTo('2026-09-04 12:00:00');

        FailedDelivery::factory()->create([
            'user_id' => $this->user->id,
            'email_type' => 'IR',
            'code' => FailedDelivery::CODE_ALIAS_DELETED,
        ]);
        FailedDelivery::factory()->create([
            'user_id' => $this->user->id,
            'email_type' => 'F',
            'code' => '550 5.1.1 User unknown',
        ]);

        $this->json('GET', '/api/v1/chart-data')
            ->assertSuccessful()
            ->assertJsonPath('failedDeliveriesTotal', 2);

        $this->user->update(['show_intentional_failed_deliveries' => false]);

        $this->json('GET', '/api/v1/chart-data')
            ->assertSuccessful()
            ->assertJsonPath('failedDeliveriesTotal', 1);
    }

    #[Test]
    public function it_orders_chart_labels_from_oldest_to_newest_across_the_last_7_days(): void
    {
        $this->travelTo('2026-09-02 12:00:00');

        $response = $this->json('GET', '/api/v1/chart-data');

        $response->assertSuccessful();
        $this->assertSame(
            ['Thursday', 'Friday', 'Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday'],
            array_values($response->json('labels'))
        );
    }
}
