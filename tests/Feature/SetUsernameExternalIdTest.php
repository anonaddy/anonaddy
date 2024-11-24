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

class SetUsernameExternalIdTest extends TestCase
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
        ->artisan('anonaddy:set-username-externalid')
        ->assertExitCode(1);
    }

    #[Test]
    public function handle_validation_should_fail_when_external_id_not_provided()
    {
        $this->expectException(\Symfony\Component\Console\Exception\RuntimeException::class);
        $this
        ->artisan('anonaddy:set-username-externalid test_username')
        ->assertExitCode(1);
    }

    #[Test]
    public function handle_validation_should_fail_when_provided_username_not_exists()
    {
        $this
        ->artisan('anonaddy:set-username-externalid test_username test_external_id')
        ->assertExitCode(1);
    }

    #[Test]
    public function handle_validation_should_fail_when_provided_external_id_already_in_use()
    {
        $this
        ->artisan('anonaddy:set-username-externalid johndoe janedoe_ext_id')
        ->assertExitCode(1);
    }

    #[Test]
    public function handle_should_set_external_id_of_provided_username_when_invoked()
    {
        $this
        ->artisan('anonaddy:set-username-externalid johndoe johndoe_ext_id')
        ->assertExitCode(0);

        $username = Username::where('username', 'johndoe')->first();

        $this->assertThat($username->external_id, $this->equalTo('johndoe_ext_id'), 'Username has externalId');
    }

    #[Test]
    public function handle_should_set_can_login_of_provided_username_when_invoked()
    {
        $this
        ->artisan('anonaddy:set-username-externalid johndoe johndoe_ext_id')
        ->assertExitCode(0);

        $username = Username::where('username', 'johndoe')->first();

        $this->assertThat($username->can_login, $this->isTrue(), 'username can login');
    }

    #[Test]
    public function handle_should_make_username_default__username_of_user_when_invoked()
    {
        $this->userJohnDoe->usernames()->create(['username' => 'test', 'can_login' => false]);

        $this
        ->artisan('anonaddy:set-username-externalid test johndoe_ext_id')
        ->assertExitCode(0);

        $user = User::where('id', $this->userJohnDoe->id)->first();

        $this->assertThat($user->defaultUsername->username, $this->equalTo('test'), 'Default username changed');

    }

}
