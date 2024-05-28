<?php

namespace Tests;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Assert;
use Ramsey\Uuid\Uuid;

abstract class TestCase extends BaseTestCase
{
    protected $user;
    protected $original;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        config([
            'anonaddy.limit' => 1000,
            'anonaddy.additional_username_limit' => 3,
            'anonaddy.domain' => 'anonaddy.com',
            'anonaddy.all_domains' => ['anonaddy.com', 'anonaddy.me'],
            'anonaddy.dkim_signing_key' => file_get_contents(base_path('tests/keys/TestDkimSigningKey')),
        ]);

        //$this->withoutExceptionHandling();

        TestResponse::macro('data', function ($key) {
            return $this->original->getData()[$key];
        });

        EloquentCollection::macro('assertEquals', function ($items) {
            Assert::assertCount($items->count(), $this);

            $this->zip($items)->each(function ($itemPair) {
                Assert::assertTrue($itemPair[0]->is($itemPair[1]));
            });
        });
    }

    protected function setUpSanctum(): void
    {
        $this->user = $this->createUser();

        Sanctum::actingAs($this->user, []);
    }

    protected function createUser(?string $username = null, ?string $email = null, array $userAttributes = [])
    {
        $userId = Uuid::uuid4();
        $usernameId = Uuid::uuid4();
        $recipientId = Uuid::uuid4();

        $usernameAttribubes = [
            'id' => $usernameId,
            'user_id' => $userId,
        ];

        if ($username) {
            $usernameAttribubes['username'] = $username;
        }

        $recipientAttribubes = [
            'id' => $recipientId,
            'user_id' => $userId,
        ];

        if ($email) {
            $recipientAttribubes['email'] = $email;
        }

        $user = User::factory(array_merge([
            'id' => $userId,
            'default_recipient_id' => $recipientId,
            'default_username_id' => $usernameId,
        ], $userAttributes))
            ->has(\App\Models\Username::factory($usernameAttribubes), 'defaultUsername')
            ->has(\App\Models\Recipient::factory($recipientAttribubes), 'defaultRecipient')
            ->create();

        // Return correct type for tests
        return User::find($user->id)->load(['defaultUsername', 'defaultRecipient']);
    }
}
