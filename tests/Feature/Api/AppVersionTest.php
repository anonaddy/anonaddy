<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Version\Package\Facade as Version;
use Tests\TestCase;

class AppVersionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        parent::setUpPassport();
    }

    /** @test */
    public function user_can_get_app_version()
    {
        $response = $this->get('/api/v1/app-version');

        $response->assertSuccessful();

        $this->assertEquals(Version::version(), $response->json()['version']);
    }
}
