<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ShowFailedDeliveryController extends Controller
{
    public function index(Request $request)
    {
        // Validate search query
        $validated = $request->validate([
            'search' => 'nullable|string|max:50|min:2',
        ]);

        $failedDeliveries = user()
            ->failedDeliveries()
            ->with(['recipient:id,email', 'alias:id,email'])
            ->select(['alias_id', 'email_type', 'code', 'attempted_at', 'created_at', 'id', 'user_id', 'recipient_id', 'remote_mta', 'sender', 'destination', 'is_stored', 'resent'])
            ->latest()
            ->get();

        if (isset($validated['search'])) {
            $searchTerm = strtolower($validated['search']);

            $failedDeliveries = $failedDeliveries->filter(function ($failedDelivery) use ($searchTerm) {
                return Str::contains(strtolower($failedDelivery->code), $searchTerm);
            })->values();
        }

        return Inertia::render('FailedDeliveries', [
            'initialRows' => $failedDeliveries,
            'recipientOptions' => fn () => user()->verifiedRecipients()->select(['id', 'email'])->get(),
            'search' => $validated['search'] ?? null,
        ]);
    }
}
