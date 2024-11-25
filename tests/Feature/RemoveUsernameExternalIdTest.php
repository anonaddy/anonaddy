<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Username;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RemoveUsernameExternalIdTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected $userJohnDoe;
    protected $userJaneDoe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userJohnDoe = $this->createUser('johndoe', null, ['password' => Hash::make('mypassword')]);
        $this->userJaneDoe = $this->createUser('janedoe', null);    
        $this->userJaneDoe->defaultUsername->external_id = 'janedoe_ext_id';
        $this->userJaneDoe->defaultUsername->save();
    }

    #[Test]
    public function handle_validation_should_fail_when_username_not_provided()
    {
        $this->expectException(\Symfony\Component\Console\Exception\RuntimeException::class);
        $this
        ->artisan('anonaddy:remove-username-externalid')
        ->assertExitCode(1);
    }

    #[Test]
    public function handle_validation_should_fail_when_provided_username_not_exists()
    {
        $this
        ->artisan('anonaddy:remove-username-externalid test_username')
        ->assertExitCode(1);
    }

    #[Test]
    public function handle_should_remove_external_id_of_provided_username_when_invoked()
    {
        $this
        ->artisan('anonaddy:remove-username-externalid janedoe')
        ->assertExitCode(0);

        $username = Username::where('username', 'janedoe')->first();

        $this->assertThat($username->external_id, $this->equalTo(null), 'Username has no externalId');
    }
}
