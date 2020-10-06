<?php

namespace App\Http\Controllers;

class ShowAliasController extends Controller
{
    public function index()
    {
        $totals = user()
            ->aliases()
            ->withTrashed()
            ->toBase()
            ->selectRaw("sum(emails_forwarded) as forwarded")
            ->selectRaw("sum(emails_blocked) as blocked")
            ->selectRaw("sum(emails_replied) as replies")
            ->first();

        return view('aliases.index', [
            'user' => user(),
            'defaultRecipient' => user()->defaultRecipient,
            'aliases' => user()
                ->aliases()
                ->with([
                    'recipients:recipient_id,email',
                    'aliasable.defaultRecipient:id,email'
                ])
                ->latest()
                ->get(),
            'recipients' => user()->verifiedRecipients()->select(['id', 'email'])->get(),
            'totals' => $totals,
            'domain' => user()->username.'.'.config('anonaddy.domain'),
            'domainOptions' => user()->domainOptions(),
        ]);
    }
}
