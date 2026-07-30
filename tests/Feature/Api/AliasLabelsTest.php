<?php

namespace Tests\Feature\Api;

use App\Models\Alias;
use App\Models\Label;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AliasLabelsTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        parent::setUpSanctum();
    }

    #[Test]
    public function user_can_attach_labels_to_alias(): void
    {
        $alias = Alias::factory()->create(['user_id' => $this->user->id]);
        $label = Label::factory()->create(['user_id' => $this->user->id]);

        $response = $this->json('POST', '/api/v1/alias-labels', [
            'alias_id' => $alias->id,
            'label_ids' => [$label->id],
        ]);

        $response->assertSuccessful();
        $this->assertCount(1, $alias->fresh()->labels);
        $this->assertEquals($label->id, $alias->labels->first()->id);
    }

    #[Test]
    public function user_can_sync_labels_on_alias(): void
    {
        $alias = Alias::factory()->create(['user_id' => $this->user->id]);
        $label1 = Label::factory()->create(['user_id' => $this->user->id]);
        $label2 = Label::factory()->create(['user_id' => $this->user->id]);
        $alias->labels()->attach($label1->id);

        $response = $this->json('POST', '/api/v1/alias-labels', [
            'alias_id' => $alias->id,
            'label_ids' => [$label2->id],
        ]);

        $response->assertSuccessful();
        $alias->refresh();
        $this->assertCount(1, $alias->labels);
        $this->assertEquals($label2->id, $alias->labels->first()->id);
    }

    #[Test]
    public function syncing_the_same_labels_twice_does_not_error(): void
    {
        $alias = Alias::factory()->create(['user_id' => $this->user->id]);
        $label = Label::factory()->create(['user_id' => $this->user->id]);

        $payload = [
            'alias_id' => $alias->id,
            'label_ids' => [$label->id],
        ];

        $this->json('POST', '/api/v1/alias-labels', $payload)->assertSuccessful();
        $this->json('POST', '/api/v1/alias-labels', $payload)->assertSuccessful();

        $this->assertCount(1, $alias->fresh()->labels);
    }

    #[Test]
    public function user_cannot_attach_invalid_label_to_alias(): void
    {
        $alias = Alias::factory()->create(['user_id' => $this->user->id]);
        $otherUser = $this->createUser('other');
        $label = Label::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->json('POST', '/api/v1/alias-labels', [
            'alias_id' => $alias->id,
            'label_ids' => [$label->id],
        ]);

        $response->assertUnprocessable();
    }

    #[Test]
    public function user_cannot_attach_more_than_ten_labels_to_alias(): void
    {
        $alias = Alias::factory()->create(['user_id' => $this->user->id]);
        $labels = Label::factory()->count(11)->create(['user_id' => $this->user->id]);

        $response = $this->json('POST', '/api/v1/alias-labels', [
            'alias_id' => $alias->id,
            'label_ids' => $labels->pluck('id')->all(),
        ]);

        $response->assertUnprocessable();
    }

    #[Test]
    public function user_can_bulk_update_labels_on_aliases(): void
    {
        $alias1 = Alias::factory()->create(['user_id' => $this->user->id]);
        $alias2 = Alias::factory()->create(['user_id' => $this->user->id]);
        $label = Label::factory()->create(['user_id' => $this->user->id]);

        $response = $this->json('POST', '/api/v1/aliases/labels/bulk', [
            'ids' => [$alias1->id, $alias2->id],
            'label_ids' => [$label->id],
        ]);

        $response->assertSuccessful();
        $this->assertCount(1, $alias1->fresh()->labels);
        $this->assertCount(1, $alias2->fresh()->labels);
    }

    #[Test]
    public function bulk_update_labels_rejects_ids_sent_as_string(): void
    {
        $alias = Alias::factory()->create(['user_id' => $this->user->id]);
        $label = Label::factory()->create(['user_id' => $this->user->id]);

        $response = $this->json('POST', '/api/v1/aliases/labels/bulk', [
            'ids' => $alias->id,
            'label_ids' => [$label->id],
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['ids']);
    }
}
