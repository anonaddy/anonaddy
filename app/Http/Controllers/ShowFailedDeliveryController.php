<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class ShowFailedDeliveryController extends Controller
{
    public function index(Request $request)
    {
        // Validate search query
        $validated = $request->validate([
            'search' => 'nullable|string|max:50|min:2',
            'filter' => 'nullable|string|in:all,inbound,outbound,inbound_quarantined',
            'page_size' => 'nullable|integer|in:25,50,100',
        ]);

        $query = user()
            ->failedDeliveries()
            ->with(['recipient:id,email', 'alias:id,email'])
            ->select(['alias_id', 'email_type', 'code', 'attempted_at', 'created_at', 'id', 'user_id', 'recipient_id', 'remote_mta', 'sender', 'destination', 'is_stored', 'resent', 'quarantined', 'ir_dedupe_key'])
            ->latest();

        $filter = $validated['filter'] ?? 'all';

        if ($filter === 'inbound') {
            $query->where(function ($q) {
                $q->where('email_type', 'IR')
                    ->orWhereNotNull('ir_dedupe_key');
            });
        } elseif ($filter === 'outbound') {
            $query->where('email_type', '!=', 'IR')
                ->whereNull('ir_dedupe_key')
                ->where('quarantined', false);
        } elseif ($filter === 'inbound_quarantined') {
            $query->where('quarantined', true);
        }

        if (isset($validated['search'])) {
            $query->where('code', 'like', '%'.$validated['search'].'%');
        }

        $failedDeliveries = $query
            ->paginate($validated['page_size'] ?? 25)
            ->withQueryString()
            ->onEachSide(1);

        return Inertia::render('FailedDeliveries', [
            'initialRows' => fn () => $failedDeliveries,
            'recipientOptions' => fn () => user()->verifiedRecipients()->select(['id', 'email'])->get(),
            'search' => $validated['search'] ?? null,
            'initialFilter' => $filter,
            'initialPageSize' => isset($validated['page_size']) ? (int) $validated['page_size'] : 25,
        ]);
    }
}
