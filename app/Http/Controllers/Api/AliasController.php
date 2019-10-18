<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAliasRequest;
use App\Http\Requests\UpdateAliasRequest;
use App\Http\Resources\AliasResource;
use Ramsey\Uuid\Uuid;

class AliasController extends Controller
{
    public function index()
    {
        return AliasResource::collection(user()->aliases()->with('recipients')->latest()->get());
    }

    public function show($id)
    {
        $alias = user()->aliases()->findOrFail($id);

        return new AliasResource($alias->load('recipients'));
    }

    public function store(StoreAliasRequest $request)
    {
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

        return new AliasResource($alias->refresh()->load('recipients'));
    }

    public function update(UpdateAliasRequest $request, $id)
    {
        $alias = user()->aliases()->findOrFail($id);

        $alias->update(['description' => $request->description]);

        return new AliasResource($alias->refresh()->load('recipients'));
    }

    public function destroy($id)
    {
        $alias = user()->aliases()->findOrFail($id);

        $alias->recipients()->detach();

        $alias->delete();

        return response('', 204);
    }
}
