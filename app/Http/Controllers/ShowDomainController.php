<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ShowDomainController extends Controller
{
    public function index(Request $request)
    {
        // Validate search query
        $validated = $request->validate([
            'search' => 'nullable|string|max:50|min:2',
        ]);

        $domains = user()
            ->domains()
            ->select(['id', 'user_id', 'default_recipient_id', 'domain', 'description', 'active', 'catch_all', 'domain_mx_validated_at', 'domain_sending_verified_at', 'created_at'])
            ->with('defaultRecipient:id,email')
            ->withCount('aliases')
            ->latest()
            ->get();

        if (isset($validated['search'])) {
            $searchTerm = strtolower($validated['search']);

            $domains = $domains->filter(function ($domain) use ($searchTerm) {
                return Str::contains(strtolower($domain->domain), $searchTerm) || Str::contains(strtolower($domain->description), $searchTerm);
            })->values();
        }

        return Inertia::render('Domains/Index', [
            'initialRows' => $domains,
            'recipientOptions' => user()->verifiedRecipients()->select(['id', 'email'])->get(),
            'initialAaVerify' => sha1(config('anonaddy.secret').user()->id.user()->domains->count()),
            'search' => $validated['search'] ?? null,
        ]);
    }

    public function edit($id)
    {
        $domain = user()->domains()->findOrFail($id);

        return Inertia::render('Domains/Edit', [
            'initialDomain' => $domain->only(['id', 'user_id', 'domain', 'description', 'from_name', 'domain_sending_verified_at', 'domain_mx_validated_at', 'updated_at']),
        ]);
    }
}
