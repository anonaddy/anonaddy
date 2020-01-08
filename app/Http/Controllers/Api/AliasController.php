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

        if ($request->uuid === false) {
            $localPart = user()->generateRandomWordLocalPart();

            $data = [
                'email' => $localPart . '@' . $request->domain,
                'local_part' => $localPart,
            ];
        } else {
            $uuid = Uuid::uuid4();

            $data = [
                'id' => $uuid,
                'email' => $uuid . '@' . $request->domain,
                'local_part' => $uuid,
            ];
        }

        $data['domain'] = $request->domain;
        $data['description'] = $request->description;

        $alias = user()->aliases()->create($data);

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
