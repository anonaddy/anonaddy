<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ShowAliasController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'page' => [
                'nullable',
                'integer',
            ],
            'page_size' => [
                'nullable',
                'integer',
                'in:25,50,100',
            ],
            'search' => [
                'nullable',
                'string',
                'max:50',
                'min:2',
            ],
            'deleted' => [
                'nullable',
                'in:with,without,only',
                'string',
            ],
            'active' => [
                'nullable',
                'in:true,false',
                'string',
            ],
            'shared_domain' => [
                'nullable',
                'in:true,false',
                'string',
            ],
            'sort' => [
                'nullable',
                'max:20',
                'min:3',
                Rule::in([
                    'local_part',
                    'domain',
                    'email',
                    'emails_forwarded',
                    'emails_blocked',
                    'emails_replied',
                    'emails_sent',
                    'active',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    '-local_part',
                    '-domain',
                    '-email',
                    '-emails_forwarded',
                    '-emails_blocked',
                    '-emails_replied',
                    '-emails_sent',
                    '-active',
                    '-created_at',
                    '-updated_at',
                    '-deleted_at',
                ]),
            ],
            'recipient' => [
                'nullable',
                'uuid',
            ],
            'domain' => [
                'nullable',
                'uuid',
            ],
            'username' => [
                'nullable',
                'uuid',
            ],
        ]);

        $sort = $request->session()->get('aliasesSort', 'created_at');
        $direction = $request->session()->get('aliasesSortDirection', 'desc');

        if ($request->has('sort')) {
            $direction = strpos($request->input('sort'), '-') === 0 ? 'desc' : 'asc';
            $sort = ltrim($request->input('sort'), '-');

            $request->session()->put('aliasesSort', $sort);
            $request->session()->put('aliasesSortDirection', $direction);
        }

        $aliases = user()->aliases()
            ->select(['id', 'user_id', 'aliasable_id', 'aliasable_type', 'local_part', 'extension', 'email', 'domain', 'description', 'active', 'emails_forwarded', 'emails_blocked', 'emails_replied', 'emails_sent', 'created_at', 'deleted_at'])
            ->when($request->input('recipient'), function ($query, $id) {
                return $query->usesRecipientWithId($id, $id === user()->default_recipient_id);
            })
            ->when($request->input('domain'), function ($query, $id) {
                return $query->belongsToAliasable('App\Models\Domain', $id);
            })
            ->when($request->input('username'), function ($query, $id) {
                return $query->belongsToAliasable('App\Models\Username', $id);
            })
            ->when($sort !== 'created_at' || $direction !== 'desc', function ($query) use ($sort, $direction) {
                if ($sort === 'created_at') {
                    return $query->orderBy($sort, $direction);
                }

                // Secondary order by latest first
                return $query
                    ->orderBy($sort, $direction)
                    ->orderBy('created_at', 'desc');
            }, function ($query) {
                return $query->latest();
            })
            ->when($request->input('active'), function ($query, $value) {
                $active = $value === 'true' ? true : false;

                return $query->where('active', $active);
            })
            ->when($request->input('shared_domain'), function ($query, $value) {
                if ($value === 'true') {
                    return $query->whereIn('domain', config('anonaddy.all_domains'));
                }

                return $query->whereNotIn('domain', config('anonaddy.all_domains'));
            })
            ->with([
                'recipients:id,email',
                'aliasable.defaultRecipient:id,email',
            ]);

        // Check if with deleted
        if ($request->deleted === 'with') {
            $aliases->withTrashed();
        }

        if ($request->deleted === 'only') {
            $aliases->onlyTrashed();
        }

        if (isset($validated['search'])) {
            $searchTerm = strtolower($validated['search']);

            // Chunk aliases and build results array by passing &$results, this is for users with tens of thousands of aliases to prevent out of memory issues.
            $searchResults = collect();
            $aliases->chunk(10000, function ($chunkedAliases) use (&$searchResults, $searchTerm) {
                $searchResults = $searchResults->concat($chunkedAliases->filter(function ($alias) use ($searchTerm) {
                    return Str::contains(strtolower($alias->email), $searchTerm) || Str::contains(strtolower($alias->description), $searchTerm);
                })->values());
            });

            $aliases = $searchResults;
        }

        $aliases = $aliases->paginate($validated['page_size'] ?? 25)->withQueryString()->onEachSide(1);

        if ($request->has('active')) {
            $currentAliasStatus = $request->input('active') === 'true' ? 'active' : 'inactive';
        } elseif ($request->has('deleted')) {
            $currentAliasStatus = $request->input('deleted') === 'with' ? 'all' : 'deleted';
        } else {
            $currentAliasStatus = 'active_inactive';
        }

        return Inertia::render('Aliases/Index', [
            'initialRows' => fn () => $aliases,
            'recipientOptions' => fn () => user()->verifiedRecipients()->select(['id', 'email'])->get(),
            'domain' => fn () => config('anonaddy.domain'),
            'subdomain' => fn () => user()->username.'.'.config('anonaddy.domain'),
            'domainOptions' => fn () => user()->domainOptions(),
            'defaultAliasDomain' => fn () => user()->default_alias_domain,
            'defaultAliasFormat' => fn () => user()->default_alias_format,
            'search' => $validated['search'] ?? null,
            'initialPageSize' => isset($validated['page_size']) ? (int) $validated['page_size'] : 25,
            'sort' => $sort,
            'sortDirection' => $direction,
            'currentAliasStatus' => $currentAliasStatus,
            'sharedDomains' => user()->sharedDomainOptions(),
        ]);
    }

    public function edit($id)
    {
        $alias = user()->aliases()->withTrashed()->findOrFail($id);

        return Inertia::render('Aliases/Edit', [
            'initialAlias' => $alias->only(['id', 'user_id', 'local_part', 'extension', 'domain', 'email', 'active', 'description', 'from_name', 'deleted_at', 'updated_at']),
        ]);
    }
}
