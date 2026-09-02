<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DestroyFailedDeliveryBulkRequest;
use App\Http\Requests\IndexFailedDeliveryRequest;
use App\Http\Resources\FailedDeliveryResource;

class FailedDeliveryController extends Controller
{
    public function index(IndexFailedDeliveryRequest $request)
    {
        $failedDeliveries = user()
            ->failedDeliveries()
            ->with(['recipient:id,email', 'alias:id,email'])
            ->when($request->input('filter.email_type'), function ($query, $value) {
                if ($value === 'inbound') {
                    return $query->where(function ($q) {
                        $q->where('email_type', 'IR')
                            ->orWhereNotNull('ir_dedupe_key');
                    });
                } elseif ($value === 'outbound') {
                    return $query->where('email_type', '!=', 'IR')
                        ->whereNull('ir_dedupe_key')
                        ->where('quarantined', false);
                } elseif ($value === 'inbound_quarantined') {
                    return $query->where('quarantined', true);
                }
            })
            ->latest()
            ->jsonPaginate();

        return FailedDeliveryResource::collection($failedDeliveries);
    }

    public function show($id)
    {
        $failedDelivery = user()->failedDeliveries()->findOrFail($id);

        return new FailedDeliveryResource($failedDelivery->load(['recipient:id,email', 'alias:id,email']));
    }

    public function destroy($id)
    {
        $failedDelivery = user()->failedDeliveries()->findOrFail($id);

        $failedDelivery->delete();

        return response('', 204);
    }

    public function destroyBulk(DestroyFailedDeliveryBulkRequest $request)
    {
        $failedDeliveries = user()
            ->failedDeliveries()
            ->whereIn('id', $request->ids)
            ->get();

        if ($failedDeliveries->isEmpty()) {
            return response()->json(['message' => 'No failed deliveries found'], 404);
        }

        $ids = $failedDeliveries->pluck('id');

        // Delete each model so the deleting event can remove stored emails from S3.
        $failedDeliveries->each->delete();

        $count = $ids->count();

        return response()->json([
            'message' => $count === 1
                ? '1 failed delivery deleted successfully'
                : "{$count} failed deliveries deleted successfully",
            'ids' => $ids,
        ], 200);
    }
}
