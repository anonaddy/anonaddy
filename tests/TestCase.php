<?php

namespace Tests;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Assert;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

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
        $this->user = User::factory()->create()->fresh();
        Sanctum::actingAs($this->user, []);
    }
}
