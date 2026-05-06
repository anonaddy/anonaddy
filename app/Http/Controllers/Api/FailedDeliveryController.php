<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
}
