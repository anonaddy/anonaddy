<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class ShowRuleController extends Controller
{
    public function index(Request $request)
    {
        // Validate search query
        $validated = $request->validate([
            'search' => 'nullable|string|max:50|min:2',
        ]);

        return Inertia::render('Rules', [
            'initialRows' => user()
                ->rules()
                ->when($request->input('search'), function ($query, $search) {
                    return $query->where('name', 'like', '%'.$search.'%');
                })
                ->orderBy('order')
                ->get(),
            'recipientOptions' => user()->verifiedRecipients()->select(['id', 'email'])->get(),
            'search' => $validated['search'] ?? null,
        ]);
    }
}
