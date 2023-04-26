<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Username;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ShowUsernamesTest extends TestCase
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
    public function user_can_view_usernames_from_the_usernames_page()
    {
        $usernames = Username::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->get('/usernames');

        $response->assertSuccessful();
        $this->assertCount(3, $response->data('usernames'));
        $usernames->assertEquals($response->data('usernames'));
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
        $this->assertCount(3, $response->data('usernames'));
        $this->assertTrue($response->data('usernames')[0]->is($b));
        $this->assertTrue($response->data('usernames')[1]->is($c));
        $this->assertTrue($response->data('usernames')[2]->is($a));
    }
}
