<?php

namespace Tests\Feature;

use App\Enums\Theme;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ThemeTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[Test]
    public function settings_page_follows_the_system_theme_by_default(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $this->assertSame(Theme::System, $user->theme);

        $this->get(route('settings.show'))
            ->assertOk()
            ->assertSee('prefers-color-scheme: dark', false)
            ->assertInertia(fn (Assert $page) => $page
                ->where('theme', 'system')
                ->where('user.theme', 'system')
            );
    }

    #[Test]
    public function settings_page_shows_the_saved_theme(): void
    {
        $user = $this->createUser(null, null, ['theme' => Theme::Light]);
        $this->actingAs($user);

        $this->get(route('settings.show'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('theme', 'light')
                ->where('user.theme', 'light')
            );
    }

    #[Test]
    #[TestWith(['system', 'System theme enabled successfully'])]
    #[TestWith(['light', 'Light theme enabled successfully'])]
    #[TestWith(['dark', 'Dark theme enabled successfully'])]
    public function user_can_save_a_theme(string $theme, string $flash): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $this->post(route('settings.dark_mode'), [
            'theme' => $theme,
        ])
            ->assertRedirect()
            ->assertSessionHas('flash', $flash);

        $this->assertSame(Theme::from($theme), $user->refresh()->theme);
    }

    #[Test]
    public function invalid_theme_is_rejected(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $this->post(route('settings.dark_mode'), [
            'theme' => 'blue',
        ])->assertInvalid([
            'theme' => 'The selected theme is invalid.',
        ]);

        $this->assertSame(Theme::System, $user->refresh()->theme);
    }

    #[Test]
    public function missing_theme_is_rejected(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $this->post(route('settings.dark_mode'), [])->assertInvalid([
            'theme' => 'The theme field is required.',
        ]);

        $this->assertSame(Theme::System, $user->refresh()->theme);
    }

    #[Test]
    public function guest_is_redirected_when_saving_a_theme(): void
    {
        $this->post(route('settings.dark_mode'), [
            'theme' => 'dark',
        ])->assertRedirectToRoute('login');
    }
}
