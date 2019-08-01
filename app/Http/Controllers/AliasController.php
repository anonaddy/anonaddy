<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAliasRequest;
use App\Http\Resources\AliasResource;
use Ramsey\Uuid\Uuid;

class AliasController extends Controller
{
    public function index()
    {
        return view('aliases.index', [
            'defaultRecipient' => user()->defaultRecipient,
            'aliases' => user()->aliases()->with('recipients')->latest()->get(),
            'recipients' => user()->verifiedRecipients,
            'totalForwarded' => user()->totalEmailsForwarded(),
            'totalBlocked' => user()->totalEmailsBlocked(),
            'totalReplies' => user()->totalEmailsReplied(),
            'domain' => user()->username.'.'.config('anonaddy.domain'),
            'bandwidthMb' => user()->bandwidth_mb
        ]);
    }

    public function store()
    {
        if (user()->hasExceededNewAliasLimit()) {
            return response('', 429);
        }

        $uuid = Uuid::uuid4();

        $alias = user()->aliases()->create([
            'id' => $uuid,
            'email' => $uuid . '@anonaddy.me',
            'local_part' => $uuid,
            'domain' => 'anonaddy.me'
        ]);

        return new AliasResource($alias->fresh());
    }

    public function update(UpdateAliasRequest $request, $id)
    {
        $alias = user()->aliases()->findOrFail($id);

        $alias->update(['description' => $request->description]);

        return new AliasResource($alias);
    }

    public function destroy($id)
    {
        $alias = user()->aliases()->findOrFail($id);

        $alias->recipients()->detach();

        $alias->delete();

        return response('', 204);
    }
}
