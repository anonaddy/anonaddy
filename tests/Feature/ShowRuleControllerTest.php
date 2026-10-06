<?php

namespace Tests\Feature;

use App\Models\Label;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShowRuleControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[Test]
    public function rules_page_lists_only_the_signed_in_users_labels(): void
    {
        $user = $this->createUser();
        Label::factory()->create([
            'user_id' => $user->id,
            'name' => 'shopping',
        ]);
        Label::factory()->create([
            'user_id' => $user->id,
            'name' => 'news',
        ]);

        $otherUser = $this->createUser('otheruser', 'other@example.com');
        Label::factory()->create([
            'user_id' => $otherUser->id,
            'name' => 'secret',
        ]);

        $this->actingAs($user)
            ->get(route('rules.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Rules')
                ->has('labelOptions', 2)
                ->where('labelOptions.0.name', 'news')
                ->where('labelOptions.1.name', 'shopping')
                ->where('labelOptions', fn ($options) => ! collect($options)->contains(
                    fn ($option) => ($option['name'] ?? null) === 'secret'
                ))
            );
    }
}
