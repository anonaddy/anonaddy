<?php

namespace Tests\Feature;

use App\Models\Username;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ShowUsernamesTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUser();
        $this->actingAs($this->user);
    }

    /** @test */
    public function user_can_view_usernames_from_the_usernames_page()
    {
        Username::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->get('/usernames');

        $response->assertSuccessful();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('initialRows', 4, fn (Assert $page) => $page
                ->where('user_id', $this->user->id)
                ->etc()
            )
        );
    }

    /** @test */
    public function latest_usernames_are_listed_first()
    {
        $a = Username::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => Carbon::now()->subDays(15),
        ]);
        $b = Username::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => Carbon::now()->subDays(5),
        ]);
        $c = Username::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => Carbon::now()->subDays(10),
        ]);

        $response = $this->get('/usernames');

        $response->assertSuccessful();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('initialRows', 4, fn (Assert $page) => $page
                ->where('user_id', $this->user->id)
                ->etc()
            )
        );
        $this->assertTrue($response->data('page')['props']['initialRows'][1]['id'] === $b->id);
        $this->assertTrue($response->data('page')['props']['initialRows'][2]['id'] === $c->id);
        $this->assertTrue($response->data('page')['props']['initialRows'][3]['id'] === $a->id);
    }
}
