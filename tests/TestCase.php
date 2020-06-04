<?php

namespace Tests;

use App\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Assert;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'anonaddy.limit' => 1000,
            'anonaddy.domain' => 'anonaddy.com',
            'anonaddy.all_domains' => ['anonaddy.com','anonaddy.me'],
            'anonaddy.dkim_signing_key' => file_get_contents(base_path('tests/keys/TestDkimSigningKey'))
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

    protected function setUpPassport(): void
    {
        $this->user = factory(User::class)->create();
        Passport::actingAs($this->user, []);

        $clientRepository = new ClientRepository();
        $client = $clientRepository->createPersonalAccessClient(
            null,
            'Test Personal Access Client',
            config('app.url')
        );
        DB::table('oauth_personal_access_clients')->insert([
            'client_id' => $client->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('oauth_access_tokens')->insert([
            'id' => '1830c31e8e17dc4e871aa21ebe82e6cbfdd0d5781bec42631dd381119f355a911075f7e1a3dc2240',
            'name' => 'New',
            'user_id' => $this->user->id,
            'revoked' => false,
            'client_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
