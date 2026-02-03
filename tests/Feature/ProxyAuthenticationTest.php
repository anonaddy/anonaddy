<?php

namespace Tests\Feature;

use App\Enums\LoginRedirect;
use App\Models\Username;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProxyAuthenticationTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected $userJohnDoe;

    protected $userJaneDoe;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('anonaddy.use_proxy_authentication', true);
        Config::set('anonaddy.proxy_authentication_external_user_id_header', 'X-User');
        Config::set('anonaddy.proxy_authentication_username_header', 'X-Name');
        Config::set('anonaddy.proxy_authentication_email_header', 'X-Email');

        $this->userJohnDoe = $this->createUser('johndoe', null, ['password' => Hash::make('mypassword')]);
        $this->userJaneDoe = $this->createUser('janedoe', null);
        $this->userJaneDoe->defaultUsername->external_id = 'janedoe_ext_id';
        $this->userJaneDoe->defaultUsername->save();
    }

    #[Test]
    public function user_can_login_with_proxy_headers()
    {
        $response = $this->withHeaders([
            'X-User' => 'janedoe_ext_id',
            'X-Name' => 'janedoe',
            'X-Email' => 'jane@doe.com',
        ])->get('/login');

        $response
            ->assertRedirect('/')
            ->assertSessionHasNoErrors()
            ->assertSessionHas('ProxyAuthenticationExternalUserId');

        $this->assertAuthenticatedAs($this->userJaneDoe, $guard = null);
    }

    #[Test]
    public function user_can_register_with_proxy_headers()
    {
        $response = $this->withHeaders([
            'X-User' => 'foo_ext_id',
            'X-Name' => 'foo',
            'X-Email' => 'foo@bar.com',
        ])->get('/login');

        $response
            ->assertRedirect('/')
            ->assertSessionHasNoErrors()
            ->assertSessionHas('ProxyAuthenticationExternalUserId');

        $this->assertDatabaseHas('usernames', [
            'username' => 'foo',
        ]);

        $username = Username::where('username', 'foo')->first();

        $this->assertThat($username->can_login, $this->isTrue(), 'username can login');
        $this->assertThat($username->user->defaultRecipient->hasVerifiedEmail(), $this->isTrue(), 'Verified email');
        $this->assertThat($username->external_id, $this->equalTo('foo_ext_id'), 'Username has externalId');
    }

    #[Test]
    public function user_registration_fails_when_username_is_not_valid()
    {
        $response = $this->withHeaders([
            'X-User' => 'foo_ext_id',
            'X-Name' => '404',
            'X-Email' => 'foo@bar.com',
        ])->get('/login');

        $response->assertStatus(403);
    }

    #[Test]
    public function user_registration_change_preferred_username_when_already_in_use()
    {
        $userBar = $this->createUser('bar', null);
        $userBar->defaultUsername->external_id = 'bar_ext_id';
        $userBar->defaultUsername->save();

        $response = $this->withHeaders([
            'X-User' => 'foo_ext_id',
            'X-Name' => 'bar',
            'X-Email' => 'foo@bar.com',
        ])->get('/login');

        $username = Username::where('username', 'bar1')->first();

        $this->assertThat($username->can_login, $this->isTrue(), 'username can login');
        $this->assertThat($username->user->defaultRecipient->hasVerifiedEmail(), $this->isTrue(), 'Verified email');
        $this->assertThat($username->external_id, $this->equalTo('foo_ext_id'), 'Username has externalId');
    }

    #[Test]
    public function user_logged_in_with_proxy_headers_logged_out_when_proxy_headers_removed()
    {
        $response = $this
            ->actingAs($this->userJaneDoe)
            ->withSession(['ProxyAuthenticationExternalUserId' => 'janedoe'])
            ->withHeaders([])
            ->get('/');

        $response
            ->assertRedirect('/login')
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('ProxyAuthenticationExternalUserId');

        $this->assertGuest($guard = null);
    }

    #[Test]
    public function currently_normal_logged_in_user_logged_out_when_user_with_proxy_headers_provided()
    {
        $response = $this
            ->actingAs($this->userJohnDoe)
            ->withHeaders([
                'X-User' => 'janedoe_ext_id',
                'X-Name' => 'janedoe',
                'X-Email' => 'jane@doe.com',
            ])
            ->get('/');

        $response
            ->assertSessionHasNoErrors()
            ->assertSessionHas('ProxyAuthenticationExternalUserId');

        $this->assertAuthenticatedAs($this->userJaneDoe, $guard = null);
    }

    #[Test]
    public function user_logged_in_when_proxy_headers_switched()
    {
        $userBar = $this->createUser('bar', null);
        $userBar->defaultUsername->external_id = 'bar_ext_id';
        $userBar->defaultUsername->save();

        $response = $this
            ->actingAs($userBar)
            ->withSession(['ProxyAuthenticationExternalUserId' => 'bar_ext_id'])
            ->withHeaders([
                'X-User' => 'janedoe_ext_id',
                'X-Name' => 'janedoe',
                'X-Email' => 'jane@doe.com',
            ])
            ->get('/');

        $response
            ->assertSessionHasNoErrors()
            ->assertSessionHas('ProxyAuthenticationExternalUserId');

        $this->assertAuthenticatedAs($this->userJaneDoe, $guard = null);
    }

    #[Test]
    public function user_can_login_with_proxy_headers_and_be_redirected_based_on_login_redirect_successfully()
    {
        $this->withoutMiddleware(ThrottleRequestsWithRedis::class);

        $this->userJaneDoe->login_redirect = LoginRedirect::ALIASES;
        $this->userJaneDoe->save();

        $response = $this
            ->withHeaders([
                'X-User' => 'janedoe_ext_id',
                'X-Name' => 'janedoe',
                'X-Email' => 'jane@doe.com',
            ])
            ->get('/login');

        $response
            ->assertRedirect('/aliases')
            ->assertSessionHasNoErrors();
    }

    #[Test]
    public function unauthenticated_when_user_with_proxy_headers_cannot_login()
    {
        $userBar = $this->createUser('bar', null);
        $username = Username::where('username', 'bar')->first();
        $username->external_id = 'bar_ext_id';
        $username->disallowLogin();
        $username->save();

        $response = $this
            ->withHeaders([
                'X-User' => 'bar_ext_id',
                'X-Name' => 'bar',
                'X-Email' => 'bar@foo.com',
            ])
            ->get('/login');

        $response
            ->assertStatus(401);
    }
}
