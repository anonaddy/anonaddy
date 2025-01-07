<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ShowRecipientController extends Controller
{
    public function index(Request $request)
    {
        // Validate search query
        $validated = $request->validate([
            'search' => 'nullable|string|max:50|min:2',
        ]);

        $recipients = user()->recipients()
            ->select([
                'id',
                'user_id',
                'email',
                'should_encrypt',
                'fingerprint',
                'email_verified_at',
                'created_at',
            ])
            ->latest()->get();

        $count = $recipients->count();

        $recipients->each(function ($item, $key) use ($count) {
            $item['key'] = $count - $key;
        });

        if (isset($validated['search'])) {
            $searchTerm = strtolower($validated['search']);

            $recipients = $recipients->filter(function ($recipient) use ($searchTerm) {
                return Str::contains(strtolower($recipient->email), $searchTerm);
            })->values();
        }

        return Inertia::render('Recipients/Index', [
            'initialRows' => $recipients,
            // 'aliasesUsingDefaultCount' => user()->aliasesUsingDefaultCount(),
            'search' => $validated['search'] ?? null,
        ]);
    }

    public function aliasCount(Request $request)
    {
        // Validate search query
        $validated = $request->validate([
            'ids' => 'required|array|max:30|min:1',
            'ids.*' => 'required|uuid|distinct',
        ]);

        $count = user()->recipients()
            ->whereIn('id', $validated['ids'])
            ->select([
                'id',
                'user_id',
            ])->withCount([
                'aliases',
                'domainAliasesUsingAsDefault' => function (Builder $query) {
                    $query->doesntHave('recipients');
                },
                'usernameAliasesUsingAsDefault' => function (Builder $query) {
                    $query->doesntHave('recipients');
                },
            ])->latest()->get(); // Must order by the same to ensure keys match

        return response()->json([
            'count' => $count,
        ], 200);
    }

    public function edit($id)
    {
        $recipient = user()->recipients()->findOrFail($id);

        return Inertia::render('Recipients/Edit', [
            'initialRecipient' => $recipient->only(['id', 'user_id', 'email', 'can_reply_send', 'fingerprint', 'protected_headers', 'inline_encryption', 'email_verified_at', 'updated_at']),
        ]);
    }
}
