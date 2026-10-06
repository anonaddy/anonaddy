<?php

namespace Tests\Feature;

use App\Models\Domain;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HiddenAliasDomainsTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUser('johndoe');
        $this->actingAs($this->user);
    }

    #[Test]
    public function user_can_hide_domains_from_the_alias_picker(): void
    {
        Domain::factory()->create([
            'user_id' => $this->user->id,
            'domain' => 'shop.example',
            'domain_verified_at' => now(),
        ]);

        $hiddenDomains = $this->hideableDomains(2);

        $response = $this->from('/settings')->post('/settings/hidden-alias-domains', [
            'hidden_domains' => $hiddenDomains,
        ]);

        $response->assertRedirect('/settings');
        $response->assertSessionHas('flash', 'Alias Domain Picker Updated Successfully');
        $this->assertSame($hiddenDomains, $this->user->fresh()->hidden_alias_domains);

        $settings = $this->get('/settings');

        $settings->assertOk();
        $settings->assertInertia(fn (Assert $page) => $page
            ->where('domainOptions', fn ($domains) => collect($domains)->contains($hiddenDomains[0])
                && collect($domains)->contains('shop.example'))
            ->where('aliasDomainPickerGroups', fn ($groups) => collect($groups)->contains(
                fn ($group) => $group['key'] === 'custom'
                    && $group['domains'] === ['shop.example']
            ))
            ->where('hiddenAliasDomains', $hiddenDomains)
        );
    }

    #[Test]
    public function user_can_show_hidden_domains_again(): void
    {
        $domain = $this->hideableDomains(1)[0];

        $this->user->forceFill([
            'hidden_alias_domains' => [$domain],
        ])->save();

        $response = $this->from('/settings')->post('/settings/hidden-alias-domains', [
            'hidden_domains' => [],
        ]);

        $response->assertRedirect('/settings');
        $this->assertSame([], $this->user->fresh()->hidden_alias_domains);
    }

    #[Test]
    public function guest_is_redirected_to_login_when_updating_hidden_alias_domains(): void
    {
        auth()->logout();

        $response = $this->post('/settings/hidden-alias-domains', [
            'hidden_domains' => $this->hideableDomains(1),
        ]);

        $response->assertRedirectToRoute('login');
    }

    #[Test]
    public function user_cannot_hide_a_domain_outside_their_account(): void
    {
        $response = $this->from('/settings')->post('/settings/hidden-alias-domains', [
            'hidden_domains' => ['not-your-domain.test'],
        ]);

        $response->assertRedirect('/settings');
        $response->assertSessionHasErrors([
            'hidden_domains.0' => 'Select a domain from your account.',
        ]);
        $this->assertNull($this->user->fresh()->hidden_alias_domains);
    }

    #[Test]
    public function user_cannot_hide_every_alias_domain(): void
    {
        $this->user->forceFill([
            'default_alias_domain' => 'not-in-list.test',
        ])->save();

        $response = $this->from('/settings')->post('/settings/hidden-alias-domains', [
            'hidden_domains' => $this->user->domainOptions()->all(),
        ]);

        $response->assertRedirect('/settings');
        $response->assertSessionHasErrors([
            'hidden_domains' => 'Leave at least one domain visible in the alias picker.',
        ]);
        $this->assertNull($this->user->fresh()->hidden_alias_domains);
    }

    #[Test]
    public function default_alias_domain_stays_in_the_picker_when_it_is_hidden(): void
    {
        $domain = $this->hideableDomains(1)[0];

        $this->post('/settings/hidden-alias-domains', [
            'hidden_domains' => [$domain],
        ])->assertRedirect();

        $this->post('/settings/default-alias-domain', [
            'domain' => $domain,
        ])->assertRedirect();

        $this->assertSame([$domain], $this->user->fresh()->hidden_alias_domains);

        Sanctum::actingAs($this->user->fresh());

        $response = $this->getJson('/api/v1/domain-options');

        $response->assertOk();
        $this->assertContains($domain, $response->json('data'));
    }

    #[Test]
    public function saving_the_picker_does_not_keep_the_default_alias_domain_hidden(): void
    {
        $domains = $this->hideableDomains(2);

        $this->user->forceFill([
            'default_alias_domain' => $domains[0],
        ])->save();

        $response = $this->post('/settings/hidden-alias-domains', [
            'hidden_domains' => $domains,
        ]);

        $response->assertRedirect();
        $this->assertSame([$domains[1]], $this->user->fresh()->hidden_alias_domains);
    }

    #[Test]
    public function hidden_domains_are_omitted_from_alias_creation_lists(): void
    {
        $hiddenDomains = $this->hideableDomains(2);
        $visibleDomain = $this->user->domainOptions()
            ->reject(fn (string $domain) => in_array($domain, $hiddenDomains, true))
            ->first();

        $this->user->forceFill([
            'hidden_alias_domains' => $hiddenDomains,
            'default_alias_domain' => $visibleDomain,
        ])->save();

        $aliases = $this->get('/aliases');

        $aliases->assertOk();
        $aliases->assertInertia(fn (Assert $page) => $page
            ->where('domainOptions', fn ($domains) => ! collect($domains)->contains($hiddenDomains[0])
                && ! collect($domains)->contains($hiddenDomains[1])
                && collect($domains)->contains($visibleDomain))
        );

        Sanctum::actingAs($this->user->fresh());

        $api = $this->getJson('/api/v1/domain-options');

        $api->assertOk();
        $this->assertNotContains($hiddenDomains[0], $api->json('data'));
        $this->assertNotContains($hiddenDomains[1], $api->json('data'));
        $this->assertContains($visibleDomain, $api->json('data'));
    }

    #[Test]
    public function user_can_create_an_alias_on_a_hidden_domain(): void
    {
        $domain = $this->hideableDomains(1)[0];

        $this->user->forceFill([
            'hidden_alias_domains' => [$domain],
        ])->save();

        Sanctum::actingAs($this->user->fresh());

        $response = $this->postJson('/api/v1/aliases', [
            'domain' => $domain,
            'format' => 'random_words',
        ]);

        $response->assertCreated();
        $this->assertSame($domain, $this->user->aliases()->first()->domain);
    }

    #[Test]
    public function api_domain_options_keep_one_domain_when_every_domain_is_stored_as_hidden(): void
    {
        $this->user->forceFill([
            'hidden_alias_domains' => $this->user->domainOptions()->all(),
            'default_alias_domain' => 'not-in-list.test',
        ])->save();

        Sanctum::actingAs($this->user->fresh());

        $response = $this->getJson('/api/v1/domain-options');

        $response->assertOk();
        $this->assertSame(
            $this->user->domainOptions()->take(1)->values()->all(),
            $response->json('data')
        );
    }

    /**
     * @return array<int, string>
     */
    private function hideableDomains(int $count): array
    {
        return $this->user->domainOptions()
            ->reject(fn (string $domain) => $domain === $this->user->default_alias_domain)
            ->take($count)
            ->values()
            ->all();
    }
}
