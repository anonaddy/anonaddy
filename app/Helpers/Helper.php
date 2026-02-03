<?php

use App\Enums\LoginRedirect;
use App\Models\Recipient;
use App\Models\User;
use App\Models\Username;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

function user()
{
    return auth()->user();
}

function carbon(...$args)
{
    return new Carbon(...$args);
}

function randomString(int $length): string
{
    $alphabet = '0123456789abcdefghijklmnopqrstuvwxyz';

    $str = '';

    for ($i = 0; $i < $length; $i++) {
        $index = random_int(0, 35);
        $str .= $alphabet[$index];
    }

    return $str;
}

function stripEmailExtension(string $email): string
{
    if (! Str::contains($email, '@')) {
        return $email;
    }

    // Strip the email of extensions
    [$localPart, $domain] = explode('@', strtolower($email));
    // Remove plus extension from local part if present
    $localPart = Str::contains($localPart, '+') ? Str::before($localPart, '+') : $localPart;

    return $localPart.'@'.$domain;
}

/**
 * Create a new user instance
 *
 * @return \App\Models\User
 */
function createUser(string $username, string $email, ?string $password = null, bool $emailVerified = false, ?string $externalId = null)
{
    $userId = Uuid::uuid4();

    $recipient = Recipient::create([
        'email' => $email,
        'user_id' => $userId,
    ]);

    if ($emailVerified) {
        $recipient->markEmailAsVerified();
    }

    $usernameModel = Username::create([
        'username' => $username,
        'user_id' => $userId,
        'external_id' => $externalId,
    ]);

    $twoFactor = app('pragmarx.google2fa');

    $passwordHash = $password === null ? '' : Hash::make($password);

    return User::create([
        'id' => $userId,
        'default_username_id' => $usernameModel->id,
        'default_recipient_id' => $recipient->id,
        'password' => $passwordHash,
        'two_factor_secret' => $twoFactor->generateSecretKey(),
    ]);
}

function getLoginRedirectUri(): string
{
    // Dynamic redirect setting to allow users to choose to go to /aliases page instead etc.
    return match (user()->login_redirect) {
        LoginRedirect::ALIASES => '/aliases',
        LoginRedirect::RECIPIENTS => '/recipients',
        LoginRedirect::USERNAMES => '/usernames',
        LoginRedirect::DOMAINS => '/domains',
        default => '/',
    };
}

function getLoginRedirectResponse(): RedirectResponse
{
    // If the intended path is just the dashboard then ignore and use the user's login redirect instead
    $redirectTo = getLoginRedirectUri();
    $intended = session()->pull('url.intended');

    return $intended === url('/') ? redirect()->to($redirectTo) : redirect()->intended($intended ?? $redirectTo);
}

function usesExternalAuthentication(): bool
{
    return Auth::check() && user()->defaultUsername->external_id !== null;
}
