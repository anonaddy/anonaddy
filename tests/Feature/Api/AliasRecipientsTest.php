<?php

namespace Tests\Feature\Api;

use App\Models\Alias;
use App\Models\AliasRecipient;
use App\Models\Recipient;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AliasRecipientsTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        parent::setUpSanctum();
    }

    /** @test */
    public function user_can_attach_recipient_to_alias()
    {
        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $recipient = Recipient::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->json('POST', '/api/v1/alias-recipients', [
            'alias_id' => $alias->id,
            'recipient_ids' => [$recipient->id],
        ]);

        $response->assertStatus(200);
        $this->assertCount(1, $alias->recipients);
        $this->assertEquals($recipient->email, $alias->recipients[0]->email);
    }

    /** @test */
    public function user_can_attach_multiple_recipients_to_alias()
    {
        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $recipient1 = Recipient::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $recipient2 = Recipient::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $recipient3 = Recipient::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->json('POST', '/api/v1/alias-recipients', [
            'alias_id' => $alias->id,
            'recipient_ids' => [$recipient1->id, $recipient2->id, $recipient3->id],
        ]);

        $response->assertStatus(200);
        $this->assertCount(3, $alias->recipients);
    }

    /** @test */
    public function user_can_update_existing_recipients_for_alias()
    {
        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $recipient1 = Recipient::factory()->create([
            'user_id' => $this->user->id,
        ]);

        AliasRecipient::create([
            'alias' => $alias,
            'recipient' => $recipient1,
        ]);

        $recipient2 = Recipient::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $recipient3 = Recipient::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->json('POST', '/api/v1/alias-recipients', [
            'alias_id' => $alias->id,
            'recipient_ids' => [$recipient2->id, $recipient3->id],
        ]);

        $response->assertStatus(200);
        $this->assertCount(2, $alias->recipients);
    }

    /** @test */
    public function user_cannot_attach_unverified_recipient_to_alias()
    {
        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $unverifiedRecipient = Recipient::factory()->create([
            'user_id' => $this->user->id,
            'email_verified_at' => null,
        ]);

        $response = $this->json('POST', '/api/v1/alias-recipients', [
            'alias_id' => $alias->id,
            'recipient_ids' => [$unverifiedRecipient->id],
        ]);

        $response->assertStatus(422);
        $this->assertCount(0, $alias->recipients);
    }

    /** @test */
    public function user_cannot_attach_more_than_allowed_recipients_to_alias()
    {
        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $recipients = Recipient::factory()->count(11)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->json('POST', '/api/v1/alias-recipients', [
            'alias_id' => $alias->id,
            'recipient_ids' => $recipients->pluck('id'),
        ]);

        $response->assertStatus(422);
        $this->assertCount(0, $alias->recipients);
    }

    /** @test */
    public function alias_recipient_record_is_deleted_if_recipient_is_deleted()
    {
        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $recipient = Recipient::factory()->create([
            'user_id' => $this->user->id,
        ]);

        AliasRecipient::create([
            'alias' => $alias,
            'recipient' => $recipient,
        ]);

        $this->assertEquals($alias->recipients[0]->email, $recipient->email);

        $recipient->delete();
        $this->assertCount(0, AliasRecipient::all());
        $this->assertDatabaseMissing('alias_recipients', [
            'alias_id' => $alias->id,
            'recipient_id' => $recipient->id,
        ]);
    }
}
