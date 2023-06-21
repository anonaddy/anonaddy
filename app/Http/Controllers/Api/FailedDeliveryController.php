<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FailedDeliveryResource;

class FailedDeliveryController extends Controller
{
    public function index()
    {
        $failedDeliveries = user()->failedDeliveries()->with(['recipient:id,email', 'alias:id,email'])->latest();

        return FailedDeliveryResource::collection($failedDeliveries->get());
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
