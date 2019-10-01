<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAliasRequest;
use App\Http\Resources\AliasResource;
use Ramsey\Uuid\Uuid;

class AliasApiController extends Controller
{
    public function store(StoreAliasRequest $request)
    {
        if (user()->hasReachedUuidAliasLimit()) {
            return response('', 403);
        }

        if (user()->hasExceededNewAliasLimit()) {
            return response('', 429);
        }

        $uuid = Uuid::uuid4();

        $alias = user()->aliases()->create([
            'id' => $uuid,
            'email' => $uuid . '@' . $request->domain,
            'local_part' => $uuid,
            'domain' => $request->domain,
            'description' => $request->description
        ]);

        return new AliasResource($alias->fresh());
    }
}
