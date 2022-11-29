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
            ->selectRaw('ifnull(sum(emails_forwarded),0) as forwarded')
            ->selectRaw('ifnull(sum(emails_blocked),0) as blocked')
            ->selectRaw('ifnull(sum(emails_replied),0) as replies')
            ->first();

        return view('aliases.index', [
            'user' => user(),
            'defaultRecipientEmail' => user()->email,
            'aliases' => user()
                ->aliases()
                ->with([
                    'recipients:id,email',
                    'aliasable.defaultRecipient:id,email',
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
