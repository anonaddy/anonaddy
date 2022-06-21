<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexAliasRequest;
use App\Http\Requests\StoreAliasRequest;
use App\Http\Requests\UpdateAliasRequest;
use App\Http\Resources\AliasResource;
use App\Models\Domain;
use App\Models\Username;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use Pdp\Rules;
use Pdp\Domain\fromIDNA2008;

class AliasController extends Controller
{
    public function index(IndexAliasRequest $request)
    {
        $aliases = user()->aliases()->with('recipients')
            ->when($request->input('sort'), function ($query, $sort) {
                $direction = strpos($sort, '-') === 0 ? 'desc' : 'asc';

                return $query->orderBy(ltrim($sort, '-'), $direction);
            }, function ($query) {
                return $query->latest();
            })
            ->when($request->input('filter.active'), function ($query, $value) {
                $active = $value === 'true' ? true : false;

                return $query->where('active', $active);
            });

        // Keep /aliases?deleted=with for backwards compatibility
        if ($request->deleted === 'with' || $request->input('filter.deleted') === 'with') {
            $aliases->withTrashed();
        }

        if ($request->deleted === 'only' || $request->input('filter.deleted') === 'only') {
            $aliases->onlyTrashed();
        }

        if ($request->input('filter.search')) {
            $searchTerm = strtolower($request->input('filter.search'));

            $aliases = $aliases->get()->filter(function ($alias) use ($searchTerm) {
                return Str::contains(strtolower($alias->email), $searchTerm) || Str::contains(strtolower($alias->description), $searchTerm);
            })->values();
        }

        $aliases = $aliases->jsonPaginate($request->input('page.size') ?? 100);

        return AliasResource::collection($aliases);
    }

    public function show($id)
    {
        $alias = user()->aliases()->withTrashed()->findOrFail($id);

        return new AliasResource($alias->load('recipients'));
    }

    public function store(StoreAliasRequest $request)
    {
        if (user()->hasExceededNewAliasLimit()) {
            return response('You have reached your hourly limit for creating new aliases', 429);
        }

        $formatEmail = function(string $localPart, string $domain, string $prefix = '') {
            return $prefix . '.' . $localPart . '@' . $domain;
        };

        if (isset($request->validated()['local_part'])) {
            $localPart = $request->validated()['local_part'];

            // Local part has extension
            if (Str::contains($localPart, '+')) {
                $extension = Str::after($localPart, '+');
                $localPart = Str::before($localPart, '+');
            }

            $data = [
                'email' => $localPart . '@' . $request->domain,
                'local_part' => $localPart,
                'extension' => $extension ?? null
            ];
        } else {
            $prefix = null;
            if (isset($request->validated()['hostname'])) {
                // TODO this should be cached, perhaps on boot, from https://publicsuffix.org/
                // see https://github.com/jeremykendall/php-domain-parser
                $publicSuffixList = Rules::fromPath(sys_get_temp_dir() . '/public_suffix_list.dat');

                $hostname = $request->validated()['hostname'];
                $domain = Domain::fromIDNA2008($hostname);
                $prefix = $publicSuffixList->resolve($domain)->secondLevelDomain()->toString();
            }

            if ($request->input('format', 'random_characters') === 'random_words') {
                $localPart = user()->generateRandomWordLocalPart();

                $data = [
                    'email' => formatEmail($localPart, $request->domain, $prefix),
                    'local_part' => $localPart,
                ];
            } elseif ($request->input('format', 'random_characters') === 'random_characters') {
                $localPart = user()->generateRandomCharacterLocalPart(8);

                $data = [
                    'email' => formatEmail($localPart, $request->domain, $prefix),
                    'local_part' => $localPart,
                ];
            } else {
                $uuid = Uuid::uuid4();

                $data = [
                    'id' => $uuid,
                    'emai' => formatEmail($uuid, $request->domain, $prefix),
                    'local_part' => $uuid,
                ];
            }
        }

        // Check if domain is for username or custom domain
        $parentDomain = collect(config('anonaddy.all_domains'))
                    ->filter(function ($name) use ($request) {
                        return Str::endsWith($request->domain, $name);
                    })
                    ->first();

        $aliasable = null;

        // This is an AnonAddy domain.
        if ($parentDomain) {
            $subdomain = substr($request->domain, 0, strrpos($request->domain, '.'.$parentDomain));

            if ($username = Username::where('username', $subdomain)->first()) {
                $aliasable = $username;
            }
        } else {
            if ($customDomain = Domain::where('domain', $request->domain)->first()) {
                $aliasable = $customDomain;
            }
        }

        $data['aliasable_id'] = $aliasable->id ?? null;
        $data['aliasable_type'] = $aliasable ? 'App\\Models\\'.class_basename($aliasable) : null;

        $data['domain'] = $request->domain;
        $data['description'] = $request->description;

        $alias = user()->aliases()->create($data);

        if ($request->recipient_ids) {
            $alias->recipients()->sync($request->recipient_ids);
        }

        return new AliasResource($alias->refresh()->load('recipients'));
    }

    public function update(UpdateAliasRequest $request, $id)
    {
        $alias = user()->aliases()->withTrashed()->findOrFail($id);

        $alias->update(['description' => $request->description]);

        return new AliasResource($alias->refresh()->load('recipients'));
    }

    public function restore($id)
    {
        $alias = user()->aliases()->withTrashed()->findOrFail($id);

        $alias->restore();

        return new AliasResource($alias->refresh()->load('recipients'));
    }

    public function destroy($id)
    {
        $alias = user()->aliases()->findOrFail($id);

        $alias->recipients()->detach();

        $alias->delete();

        return response('', 204);
    }

    public function forget($id)
    {
        $alias = user()->aliases()->withTrashed()->findOrFail($id);

        $alias->recipients()->detach();

        if ($alias->hasSharedDomain()) {
            // Remove all data from the alias and change user_id
            $alias->update([
                'user_id' => '00000000-0000-0000-0000-000000000000',
                'extension' => null,
                'description' => null,
                'emails_forwarded' => 0,
                'emails_blocked' => 0,
                'emails_replied' => 0,
                'emails_sent' => 0
            ]);

            // Soft delete to prevent from being regenerated
            $alias->delete();
        } else {
            $alias->forceDelete();
        }

        return response('', 204);
    }
}
