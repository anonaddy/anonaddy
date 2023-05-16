<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateStoreFailedDeliveryRequest;

class StoreFailedDeliveryController extends Controller
{
    public function update(UpdateStoreFailedDeliveryRequest $request)
    {
        if ($request->store_failed_deliveries) {
            user()->update(['store_failed_deliveries' => true]);
        } else {
            user()->update(['store_failed_deliveries' => false]);
        }

        return back()->with(['status' => $request->store_failed_deliveries ? 'Store Failed Deliveries Enabled Successfully' : 'Store Failed Deliveries Disabled Successfully']);
    }
}
