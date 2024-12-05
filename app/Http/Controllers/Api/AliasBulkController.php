<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GeneralAliasBulkRequest;
use App\Http\Requests\RecipientsAliasBulkRequest;
use App\Http\Resources\AliasResource;
use App\Rules\VerifiedRecipientId;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class AliasBulkController extends Controller
{
    public function __construct()
    {
        $this->middleware('throttle:12,1');
    }

    public function get(GeneralAliasBulkRequest $request)
    {
        $aliases = user()->aliases()->withTrashed()
            ->whereIn('id', $request->ids)
            ->get();

        // If there are no aliases found return 404 response
        if (! $aliases->count()) {
            return response()->json(['message' => 'No aliases found'], 404);
        }

        return AliasResource::collection($aliases);
    }

    public function activate(GeneralAliasBulkRequest $request)
    {
        $aliasesWithTrashed = user()->aliases()->withTrashed()
            ->select(['id', 'user_id', 'active', 'deleted_at'])
            ->where('active', false)
            ->whereIn('id', $request->ids)
            ->get();

        // If there are no aliases found return 404 response
        if (! $aliasesWithTrashedCount = $aliasesWithTrashed->count()) {
            return response()->json(['message' => 'No aliases found'], 404);
        }

        // Check if all aliases are deleted, if so return message
        $aliases = $aliasesWithTrashed->filter(function ($alias) {
            return ! $alias->trashed();
        });

        if ($aliases->count() === 0) {
            return response()->json([
                'message' => $aliasesWithTrashedCount === 1 ? 'You need to restore this alias before you can activate it' : 'You need to restore these aliases before you can activate them',
                'ids' => $aliasesWithTrashed->pluck('id'),
            ], 422);
        }

        $aliasIds = $aliases->pluck('id')->all();
        $aliasIdsCount = count($aliasIds);
        user()->aliases()->whereIn('id', $aliasIds)->update(['active' => true]);

        return response()->json([
            'message' => $aliasIdsCount === 1 ? '1 alias activated successfully' : "{$aliasIdsCount} aliases activated successfully",
            'ids' => $aliasIds,
        ], 200);
    }

    public function deactivate(GeneralAliasBulkRequest $request)
    {
        $aliasIds = user()->aliases()
            ->where('active', true)
            ->whereIn('id', $request->ids)
            ->pluck('id');

        // If there are no aliases found return 404 response
        if (! $aliasIdsCount = $aliasIds->count()) {
            return response()->json(['message' => 'No aliases found'], 404);
        }

        user()->aliases()->whereIn('id', $aliasIds)->update(['active' => false]);

        return response()->json([
            'message' => $aliasIdsCount === 1 ? '1 alias deactivated successfully' : "{$aliasIdsCount} aliases deactivated successfully",
            'ids' => $aliasIds,
        ], 200);
    }

    public function delete(GeneralAliasBulkRequest $request)
    {
        $aliasIds = user()->aliases()
            ->whereIn('id', $request->ids)
            ->pluck('id');

        // If there are no aliases found return 404 response
        if (! $aliasIdsCount = $aliasIds->count()) {
            return response()->json(['message' => 'No aliases found'], 404);
        }

        // Detach any recipients
        DB::table('alias_recipients')->whereIn('alias_id', $aliasIds)->delete();

        // Use update since delete() does not trigger model event
        user()->aliases()->whereIn('id', $aliasIds)->update(['active' => false, 'deleted_at' => now()]);

        // Don't return 204 as that is only for empty responses
        return response()->json([
            'message' => $aliasIdsCount === 1 ? '1 alias deleted successfully' : "{$aliasIdsCount} aliases deleted successfully",
            'ids' => $aliasIds,
        ], 200);
    }

    public function forget(GeneralAliasBulkRequest $request)
    {
        $aliasIds = user()->aliases()->withTrashed()
            ->whereIn('id', $request->ids)
            ->pluck('id');

        // If there are no aliases found return 404 response
        if (! $aliasIdsCount = $aliasIds->count()) {
            return response()->json(['message' => 'No aliases found'], 404);
        }

        // Detach any recipients
        DB::table('alias_recipients')->whereIn('alias_id', $aliasIds)->delete();

        // Shared Domain aliases, remove all data and change user_id
        $forgottenSharedDomainCount = user()->aliases()->withTrashed()
            ->whereIn('id', $aliasIds)
            ->whereIn('domain', config('anonaddy.all_domains'))
            ->update([
                'user_id' => '00000000-0000-0000-0000-000000000000',
                'extension' => null,
                'description' => null,
                'emails_forwarded' => 0,
                'emails_blocked' => 0,
                'emails_replied' => 0,
                'emails_sent' => 0,
                'last_forwarded' => null,
                'last_blocked' => null,
                'last_replied' => null,
                'last_sent' => null,
                'active' => false,
                'deleted_at' => now(),
            ]);

        if ($forgottenSharedDomainCount < $aliasIdsCount) {
            // Standard aliases
            user()->aliases()->withTrashed()
                ->whereIn('id', $aliasIds)
                ->whereNotIn('domain', config('anonaddy.all_domains'))
                ->forceDelete();
        }

        // Don't return 204 as that is only for empty responses
        return response()->json([
            'message' => $aliasIdsCount === 1 ? '1 alias forgotten successfully' : "{$aliasIdsCount} aliases forgotten successfully",
            'ids' => $aliasIds,
        ], 200);
    }

    public function restore(GeneralAliasBulkRequest $request)
    {
        $aliasIds = user()->aliases()->onlyTrashed()
            ->whereIn('id', $request->ids)
            ->pluck('id');

        // If there are no aliases found return 404 response
        if (! $aliasIdsCount = $aliasIds->count()) {
            return response()->json(['message' => 'No aliases found'], 404);
        }

        // Use update since delete() does not trigger model event
        user()->aliases()->onlyTrashed()->whereIn('id', $aliasIds)->update(['active' => true, 'deleted_at' => null]);

        // Don't return 204 as that is only for empty responses
        return response()->json([
            'message' => $aliasIdsCount === 1 ? '1 alias restored successfully' : "{$aliasIdsCount} aliases restored successfully",
            'ids' => $aliasIds,
        ], 200);
    }

    public function recipients(RecipientsAliasBulkRequest $request)
    {
        $request->validate([
            'ids' => 'required|array|max:25|min:1',
            'ids.*' => 'required|uuid|distinct',
            'recipient_ids' => [
                'array',
                'max:10',
                new VerifiedRecipientId,
            ],
            'recipient_ids.*' => 'required|uuid|distinct',
        ]);

        $aliasIds = user()->aliases()->withTrashed()
            ->whereIn('id', $request->ids)
            ->pluck('id');

        // If there are no aliases found return 404 response
        if (! $aliasIdsCount = $aliasIds->count()) {
            return response()->json(['message' => 'No aliases found'], 404);
        }

        // First delete existing alias recipients
        DB::table('alias_recipients')->whereIn('alias_id', $aliasIds)->delete();
        // Then create alias recipients
        DB::table('alias_recipients')->insert((collect($aliasIds))->flatMap(function ($aliasId) use ($request) {
            $val = [];
            foreach ($request->recipient_ids as $recipientId) {
                $val[] = [
                    'id' => Uuid::uuid4(),
                    'alias_id' => $aliasId,
                    'recipient_id' => $recipientId,
                ];
            }

            return $val;
        })->all());

        // Don't return 204 as that is only for empty responses
        return response()->json([
            'message' => $aliasIdsCount === 1 ? 'recipients updated for 1 alias successfully' : "recipients updated for {$aliasIdsCount} aliases successfully",
            'ids' => $aliasIds,
        ], 200);
    }
}
