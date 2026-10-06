<?php

namespace App\Http\Controllers;

use App\Models\BlockedSender;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AliasBannerActionsController extends Controller
{
    private const DOMAIN_PATTERN = '/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i';

    public function __construct()
    {
        $this->middleware('signed')->only('show');
        $this->middleware('throttle:6,1');
    }

    public function show(Request $request, string $alias): Response
    {
        $aliasModel = user()->aliases()->findOrFail($alias);
        $senderEmail = $this->normalisedSenderEmail($request->query('email'));
        $senderDomain = $this->senderDomainFromEmail($senderEmail);
        $action = $request->query('action');
        $action = is_string($action) && in_array($action, ['block_email', 'block_domain'], true)
            ? $action
            : null;

        return Inertia::render('Aliases/Actions', [
            'alias' => $aliasModel->only(['id', 'email', 'description', 'active']),
            'senderEmail' => $senderEmail,
            'senderDomain' => $senderDomain,
            'action' => $action,
            'canBlockDomain' => $senderDomain !== null && ! BlockedSender::isProtectedAliasDomain($senderDomain),
        ]);
    }

    public function deactivate(string $alias): RedirectResponse
    {
        $aliasModel = user()->aliases()->findOrFail($alias);

        $aliasModel->deactivate();

        Log::info('Alias deactivated via banner actions: '.user()->username.' alias: '.$aliasModel->email.' ID: '.$alias);

        return redirect()->route('aliases.edit', $aliasModel->id)
            ->with(['flash' => 'Alias '.$aliasModel->email.' deactivated successfully!']);
    }

    public function blockEmail(Request $request, string $alias): RedirectResponse
    {
        user()->aliases()->findOrFail($alias);

        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:253'],
        ]);

        $value = Str::lower(trim($validated['email']));

        try {
            user()->blockedSenders()->create([
                'type' => 'email',
                'value' => $value,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'email' => BlockedSender::ALREADY_ON_BLOCKLIST_MESSAGE,
            ]);
        }

        Log::info('Sender email blocked via banner actions: '.$value.' for user: '.user()->username);

        return redirect()->route('blocklist.index')
            ->with(['flash' => $value.' added to your blocklist.']);
    }

    public function blockDomain(Request $request, string $alias): RedirectResponse
    {
        user()->aliases()->findOrFail($alias);

        $validated = $request->validate([
            'domain' => ['required', 'string', 'max:253', 'regex:'.self::DOMAIN_PATTERN],
        ]);

        $value = Str::lower(trim($validated['domain']));

        if (BlockedSender::isProtectedAliasDomain($value)) {
            throw ValidationException::withMessages([
                'domain' => 'You cannot block this domain.',
            ]);
        }

        try {
            user()->blockedSenders()->create([
                'type' => 'domain',
                'value' => $value,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'domain' => BlockedSender::ALREADY_ON_BLOCKLIST_MESSAGE,
            ]);
        }

        Log::info('Sender domain blocked via banner actions: '.$value.' for user: '.user()->username);

        return redirect()->route('blocklist.index')
            ->with(['flash' => $value.' added to your blocklist.']);
    }

    private function normalisedSenderEmail(mixed $email): ?string
    {
        if (! is_string($email) || $email === '') {
            return null;
        }

        $email = Str::lower(trim($email));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $email;
    }

    private function senderDomainFromEmail(?string $email): ?string
    {
        if ($email === null || ! str_contains($email, '@')) {
            return null;
        }

        $domain = Str::lower(Str::afterLast($email, '@'));

        if ($domain === '' || strlen($domain) > 253) {
            return null;
        }

        return $domain;
    }
}
