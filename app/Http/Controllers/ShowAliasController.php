<?php

namespace App\Http\Controllers;

class ShowAliasController extends Controller
{
    public function index()
    {
        return view('aliases.index', [
            'defaultRecipient' => user()->defaultRecipient,
            'aliases' => user()->aliases()->with(['recipients', 'aliasable.defaultRecipient'])->latest()->get(),
            'recipients' => user()->verifiedRecipients,
            'totalForwarded' => user()->totalEmailsForwarded(),
            'totalBlocked' => user()->totalEmailsBlocked(),
            'totalReplies' => user()->totalEmailsReplied(),
            'domain' => user()->username.'.'.config('anonaddy.domain'),
            'bandwidthMb' => user()->bandwidth_mb,
            'domainOptions' => user()->domainOptions(),
            'defaultAliasDomain' => user()->default_alias_domain,
            'defaultAliasFormat' => user()->default_alias_format
        ]);
    }
}
