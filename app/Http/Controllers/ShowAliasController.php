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
                'in:true,false,both',
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
                    'last_forwarded',
                    'last_blocked',
                    'last_replied',
                    'last_sent',
                    'last_used',
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
                    '-last_forwarded',
                    '-last_blocked',
                    '-last_replied',
                    '-last_sent',
                    '-last_used',
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
        $compareOperator = $request->session()->get('aliasesSortCompareOperator', '>');

        // current alias status options: active, inactive, all, deleted, active_inactive
        $currentAliasStatus = $request->session()->get('currentAliasStatus', 'active_inactive');

        if ($request->has('sort')) {
            $direction = strpos($request->input('sort'), '-') === 0 ? 'desc' : 'asc';
            $sort = ltrim($request->input('sort'), '-');
            $compareOperator = $direction === 'desc' ? '>' : '<';

            $request->session()->put('aliasesSort', $sort);
            $request->session()->put('aliasesSortDirection', $direction);
        }

        if ($request->has('active')) {
            $currentAliasStatus = match ($request->input('active')) {
                'both' => 'active_inactive',
                'true' => 'active',
                'false' => 'inactive',
                default => 'active_inactive',
            };

            $request->session()->put('currentAliasStatus', $currentAliasStatus);
        } elseif ($request->has('deleted')) {
            $currentAliasStatus = $request->input('deleted') === 'with' ? 'all' : 'deleted';
            $request->session()->put('currentAliasStatus', $currentAliasStatus);
        }

        $aliases = user()->aliases()
            ->select(['id', 'user_id', 'aliasable_id', 'aliasable_type', 'local_part', 'extension', 'email', 'domain', 'description', 'active', 'emails_forwarded', 'emails_blocked', 'emails_replied', 'emails_sent', 'last_forwarded', 'last_blocked', 'last_replied', 'last_sent', 'created_at', 'deleted_at'])
            ->when($request->input('recipient'), function ($query, $id) {
                return $query->usesRecipientWithId($id, $id === user()->default_recipient_id);
            })
            ->when($request->input('domain'), function ($query, $id) {
                return $query->belongsToAliasable('App\Models\Domain', $id);
            })
            ->when($request->input('username'), function ($query, $id) {
                return $query->belongsToAliasable('App\Models\Username', $id);
            })
            ->when($sort !== 'created_at' || $direction !== 'desc', function ($query) use ($sort, $direction, $compareOperator) {
                if ($sort === 'created_at') {
                    return $query->orderBy($sort, $direction);
                }

                // If sort is last_used then order by all and return
                if ($sort === 'last_used') {
                    return $query
                        ->orderByRaw(
                            "CASE
                            WHEN (last_forwarded {$compareOperator} last_replied
                            OR (last_forwarded IS NOT NULL
                            AND last_replied IS NULL))
                            AND (last_forwarded {$compareOperator} last_sent
                            OR (last_forwarded IS NOT NULL
                            AND last_sent IS NULL))
                                THEN last_forwarded
                            WHEN last_replied {$compareOperator} last_sent
                            OR (last_replied IS NOT NULL
                            AND last_sent IS NULL)
                                THEN last_replied
                            ELSE last_sent
                        END {$direction}"
                        )->orderBy('created_at', 'desc');
                }

                // Secondary order by latest first
                return $query
                    ->orderBy($sort, $direction)
                    ->orderBy('created_at', 'desc');
            }, function ($query) {
                return $query->latest();
            })
            ->when(in_array($currentAliasStatus, ['active', 'inactive']), function ($query, $value) use ($currentAliasStatus) {
                $active = $currentAliasStatus === 'active' ? true : false;

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
        if ($currentAliasStatus === 'all') {
            $aliases->withTrashed();
        }

        if ($currentAliasStatus === 'deleted') {
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

        return Inertia::render('Aliases/Index', [
            'initialRows' => fn () => $aliases,
            'recipientOptions' => fn () => user()->verifiedRecipients()->select(['id', 'email'])->get(),
            'domain' => fn () => user()->canCreateSharedDomainAliases() ? config('anonaddy.domain') : null,
            'subdomain' => fn () => user()->canCreateUsernameSubdomainAliases() ? user()->username.'.'.config('anonaddy.domain') : null,
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
            'initialAlias' => $alias->only(['id', 'user_id', 'local_part', 'extension', 'domain', 'email', 'active', 'description', 'from_name', 'attached_recipients_only', 'deleted_at', 'updated_at']),
        ]);
    }
}
