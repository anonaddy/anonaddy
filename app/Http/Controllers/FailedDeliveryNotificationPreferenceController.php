<?php

namespace App\Http\Controllers;

use App\Enums\FailedDeliveryNotificationPreference;
use App\Http\Requests\UpdateFailedDeliveryNotificationPreferenceRequest;

class FailedDeliveryNotificationPreferenceController extends Controller
{
    public function update(UpdateFailedDeliveryNotificationPreferenceRequest $request)
    {
        user()->update([
            'failed_delivery_notification_preference' => FailedDeliveryNotificationPreference::from($request->integer('failed_delivery_notification_preference')),
        ]);

        return back()->with(['flash' => 'Failed delivery notification preference updated successfully']);
    }
}
