<?php

namespace App\Http\Controllers;

class ShowRecipientController extends Controller
{
    public function index()
    {
        $recipients = user()->recipients()->with([
            'aliases:id,aliasable_id,email',
            'domainsUsingAsDefault.aliases:id,aliasable_id,email',
            'usernamesUsingAsDefault.aliases:id,aliasable_id,email',
        ])->latest()->get();

        $recipients->each(function ($recipient) {
            if ($recipient->domainsUsingAsDefault) {
                $domainAliases = $recipient->domainsUsingAsDefault->flatMap(function ($domain) {
                    return $domain->aliases;
                });
                $recipient->setRelation('aliases', $recipient->aliases->concat($domainAliases)->unique('email'));
            }

            if ($recipient->usernamesUsingAsDefault) {
                $usernameAliases = $recipient->usernamesUsingAsDefault->flatMap(function ($domain) {
                    return $domain->aliases;
                });
                $recipient->setRelation('aliases', $recipient->aliases->concat($usernameAliases)->unique('email'));
            }
        });

        $count = $recipients->count();

        $recipients->each(function ($item, $key) use ($count) {
            $item['key'] = $count - $key;
        });

        return view('recipients.index', [
            'recipients' => $recipients,
            'aliasesUsingDefault' => user()->aliasesUsingDefault()->take(5)->get(),
            'aliasesUsingDefaultCount' => user()->aliasesUsingDefault()->count(),
            'user' => user()->load('defaultUsername'),
        ]);
    }
}
