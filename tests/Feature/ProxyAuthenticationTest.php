<?php

namespace Tests\Feature;

use App\Enums\LoginRedirect;
use App\Models\Username;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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
        Config::set('anonaddy.proxy_authentication_username_header', 'X-Name');
        Config::set('anonaddy.proxy_authentication_email_header', 'X-Email');

        $this->userJohnDoe = $this->createUser('johndoe', null, ['password' => Hash::make('mypassword')]);
        $this->userJaneDoe = $this->createUser('janedoe', null);    
    }

    #[Test]
    public function user_can_login_with_proxy_headers()
    {
        $response = $this->withHeaders([
            'X-Name' => 'janedoe',
            'X-Email' => 'jane@doe.com'
        ])->get('/login');

        $response
            ->assertRedirect('/')
            ->assertSessionHasNoErrors()
            ->assertSessionHas('ProxyAuthenticationUsername');     

        $this->assertAuthenticatedAs($this->userJaneDoe, $guard = null);
    }

    #[Test]
    public function user_can_register_with_proxy_headers()
    {
        $response = $this->withHeaders([
            'X-Name' => 'foo',
            'X-Email' => 'foo@bar.com'
        ])->get('/login');

        $response
            ->assertRedirect('/')
            ->assertSessionHasNoErrors()
            ->assertSessionHas('ProxyAuthenticationUsername');

        $this->assertDatabaseHas('usernames', [
            'username' => 'foo',
        ]);

        $username = Username::where('username', 'foo')->first();

        $this->assertThat($username->can_login, $this->isTrue(), 'username can login');
        $this->assertThat($username->user->defaultRecipient->hasVerifiedEmail(), $this->isTrue(), 'Verified email');
    }

    #[Test]
    public function user_logged_in_with_proxy_headers_logged_out_when_proxy_headers_removed()
    {
        $response = $this
            ->actingAs($this->userJaneDoe)
            ->withSession(['ProxyAuthenticationUsername' => 'janedoe'])
            ->withHeaders([])
            ->get('/');

        $response
            ->assertRedirect('/login')
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('ProxyAuthenticationUsername');

        $this->assertGuest($guard = null);
    }

    #[Test]
    public function currently_normal_logged_in_user_logged_out_when_user_with_proxy_headers_provided()
    {
        $response = $this
            ->actingAs($this->userJohnDoe)
            ->withHeaders([
                'X-Name' => 'janedoe',
                'X-Email' => 'jane@doe.com'
            ])
            ->get('/');

        $response
            ->assertSessionHasNoErrors()
            ->assertSessionHas('ProxyAuthenticationUsername'); 

        $this->assertAuthenticatedAs($this->userJaneDoe, $guard = null);
    }

    #[Test]
    public function user_logged_in_when_proxy_headers_switched()
    {
        $userBar = $this->createUser('bar', null);   

        $response = $this
            ->actingAs($userBar)
            ->withSession(['ProxyAuthenticationUsername' => 'bar'])
            ->withHeaders([
                'X-Name' => 'janedoe',
                'X-Email' => 'jane@doe.com'
            ])
            ->get('/');

        $response
        ->assertSessionHasNoErrors()
        ->assertSessionHas('ProxyAuthenticationUsername'); 

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
                'X-Name' => 'janedoe',
                'X-Email' => 'jane@doe.com'
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
        $username->disallowLogin();
        $username->save();

        
        $response = $this
            ->withHeaders([
                'X-Name' => 'bar',
                'X-Email' => 'bar@foo.com'
            ])
            ->get('/login');

        $response
            ->assertStatus(401);
    }

    #[Test]
    public function unauthenticated_when_user_with_proxy_headers_deactivated()
    {
        $userBar = $this->createUser('bar', null); 
        $username = Username::where('username', 'bar')->first();
        $username->deactivate();
        $username->save();

        
        $response = $this
            ->withHeaders([
                'X-Name' => 'bar',
                'X-Email' => 'bar@foo.com'
            ])
            ->get('/login');

        $response
            ->assertStatus(401);
    }
}
