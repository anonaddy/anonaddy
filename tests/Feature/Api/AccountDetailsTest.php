<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AccountDetailsTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        parent::setUpSanctum();
    }

    /** @test */
    public function user_can_get_account_details()
    {
        $response = $this->json('GET', '/api/v1/account-details');

        $response->assertSuccessful();

        $this->assertEquals($this->user->username, $response->json()['data']['username']);
    }
}
