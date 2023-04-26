<?php

namespace Tests\Feature\Api;

use App\Helpers\GitVersionHelper as Version;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AppVersionTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        parent::setUpSanctum();
    }

    /** @test */
    public function user_can_get_app_version()
    {
        $response = $this->json('GET', '/api/v1/app-version');

        $response->assertSuccessful();

        $this->assertEquals(Version::version(), $response->json()['version']);
    }
}
