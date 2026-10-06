<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DestroyBlocklistBulkRequest;
use App\Http\Requests\StoreBlockedSenderRequest;
use App\Http\Requests\StoreBlocklistBulkRequest;
use App\Http\Resources\BlocklistResource;
use App\Models\BlockedSender;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BlocklistController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'filter' => 'nullable|array',
            'filter.search' => 'nullable|string|max:100|min:1',
            'filter.type' => ['nullable', Rule::in(['email', 'domain'])],
            'sort' => ['nullable', Rule::in(['created_at', 'value', 'blocked', 'last_blocked', '-created_at', '-value', '-blocked', '-last_blocked'])],
            'page.number' => 'nullable|integer|min:1',
            'page.size' => 'nullable|integer|min:1|max:100',
        ]);

        $query = $request->user()
            ->blockedSenders()
            ->select(['id', 'user_id', 'type', 'value', 'blocked', 'last_blocked', 'updated_at', 'created_at'])
            ->latest();

        $searchTerm = data_get($validated, 'filter.search');

        if ($searchTerm !== null) {
            $searchTerm = strtolower($searchTerm);
            $query->where(function ($q) use ($searchTerm) {
                $q->whereRaw('LOWER(value) LIKE ?', ['%'.$searchTerm.'%']);
            });
        }

        if (isset($validated['filter']['type'])) {
            $query->where('type', $validated['filter']['type']);
        }

        $sort = $validated['sort'] ?? '-created_at';
        $sortDirection = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $sortColumn = ltrim($sort, '-');
        $query->orderBy($sortColumn, $sortDirection);

        return BlocklistResource::collection($query->jsonPaginate($request->input('page.size') ?? 100));
    }

    public function store(StoreBlockedSenderRequest $request)
    {
        try {
            $blockedSender = $request->user()->blockedSenders()->create($request->validated());
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'value' => BlockedSender::ALREADY_ON_BLOCKLIST_MESSAGE,
            ]);
        }

        return new BlocklistResource($blockedSender->refresh());
    }

    public function storeBulk(StoreBlocklistBulkRequest $request)
    {
        $type = $request->input('type');
        $values = array_values(array_unique($request->input('values')));

        $existing = $request->user()
            ->blockedSenders()
            ->where('type', $type)
            ->whereIn('value', $values)
            ->pluck('value')
            ->all();

        $toCreate = array_values(array_diff($values, $existing));
        $skipped = count($values) - count($toCreate);

        $createdModels = collect();

        foreach ($toCreate as $value) {
            try {
                $createdModels->push($request->user()->blockedSenders()->create([
                    'type' => $type,
                    'value' => $value,
                ]));
            } catch (UniqueConstraintViolationException) {
                $skipped++;
            }
        }

        $createdModels = BlockedSender::whereIn('id', $createdModels->pluck('id'))->get();

        $count = count($createdModels);

        $data = BlocklistResource::collection($createdModels)->resolve();

        return response()->json([
            'data' => $data,
            'message' => $count === 0
                ? ($skipped > 0 ? 'All entries were already on your blocklist.' : 'No entries added.')
                : ($count === 1
                    ? '1 entry added to blocklist.'
                    : "{$count} entries added to blocklist.").($skipped > 0 ? " {$skipped} already on blocklist." : ''),
            'skipped' => $skipped,
        ], 201);
    }

    public function destroy(Request $request, string $id)
    {
        $entry = $request->user()->blockedSenders()->findOrFail($id);

        $entry->delete();

        return response('', 204);
    }

    public function destroyBulk(DestroyBlocklistBulkRequest $request)
    {
        $ids = $request->user()
            ->blockedSenders()
            ->whereIn('id', $request->ids)
            ->pluck('id');

        if ($ids->isEmpty()) {
            return response()->json(['message' => 'No blocklist entries found'], 404);
        }

        $request->user()->blockedSenders()->whereIn('id', $ids)->delete();

        $count = $ids->count();

        return response()->json([
            'message' => $count === 1
                ? '1 entry removed from blocklist'
                : "{$count} entries removed from blocklist",
            'ids' => $ids,
        ], 200);
    }
}
