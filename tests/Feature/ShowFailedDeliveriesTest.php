<?php

namespace Tests\Feature;

use App\Models\FailedDelivery;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ShowFailedDeliveriesTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create()->fresh();
        $this->user->usernames()->save($this->user->defaultUsername);
        $this->user->defaultUsername->username = 'johndoe';
        $this->user->defaultUsername->save();
        $this->actingAs($this->user);
    }

    /** @test */
    public function user_can_view_failed_deliveries_from_the_failed_deliveries_page()
    {
        $failedDeliveries = FailedDelivery::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->get('/failed-deliveries');

        $response->assertSuccessful();
        $this->assertCount(3, $response->data('failedDeliveries'));
        $failedDeliveries->assertEquals($response->data('failedDeliveries'));
    }

    /** @test */
    public function latest_failed_deliveries_are_listed_first()
    {
        $a = FailedDelivery::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => Carbon::now()->subDays(15),
        ]);
        $b = FailedDelivery::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => Carbon::now()->subDays(5),
        ]);
        $c = FailedDelivery::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => Carbon::now()->subDays(10),
        ]);

        $response = $this->get('/failed-deliveries');

        $response->assertSuccessful();
        $this->assertCount(3, $response->data('failedDeliveries'));
        $this->assertTrue($response->data('failedDeliveries')[0]->is($b));
        $this->assertTrue($response->data('failedDeliveries')[1]->is($c));
        $this->assertTrue($response->data('failedDeliveries')[2]->is($a));
    }
}
