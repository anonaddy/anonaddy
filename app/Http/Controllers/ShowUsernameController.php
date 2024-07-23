<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ShowUsernameController extends Controller
{
    public function index(Request $request)
    {
        // Validate search query
        $validated = $request->validate([
            'search' => 'nullable|string|max:50|min:2',
        ]);

        $usernames = user()
            ->usernames()
            ->select(['id', 'user_id', 'default_recipient_id', 'username', 'description', 'active', 'catch_all', 'created_at'])
            ->with('defaultRecipient:id,email')
            ->withCount('aliases')
            ->latest()
            ->get();

        if (isset($validated['search'])) {
            $searchTerm = strtolower($validated['search']);

            $usernames = $usernames->filter(function ($username) use ($searchTerm) {
                return Str::contains(strtolower($username->username), $searchTerm) || Str::contains(strtolower($username->description), $searchTerm);
            })->values();
        }

        return Inertia::render('Usernames/Index', [
            'initialRows' => $usernames,
            'recipientOptions' => user()->verifiedRecipients()->select(['id', 'email'])->get(),
            'search' => $validated['search'] ?? null,
            'usernameCount' => (int) config('anonaddy.additional_username_limit'),
        ]);
    }

    public function edit($id)
    {
        $username = user()->usernames()->findOrFail($id);

        return Inertia::render('Usernames/Edit', [
            'initialUsername' => $username->only(['id', 'user_id', 'username', 'description', 'from_name', 'can_login', 'auto_create_regex', 'updated_at']),
        ]);
    }
}
