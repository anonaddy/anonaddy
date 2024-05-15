<?php

namespace Tests\Feature;

use App\Models\FailedDelivery;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShowFailedDeliveriesTest extends TestCase
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
    public function user_can_view_failed_deliveries_from_the_failed_deliveries_page()
    {
        FailedDelivery::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->get('/failed-deliveries');

        $response->assertSuccessful();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('initialRows', 3, fn (Assert $page) => $page
                ->where('user_id', $this->user->id)
                ->etc()
            )
        );
    }

    #[Test]
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
}
