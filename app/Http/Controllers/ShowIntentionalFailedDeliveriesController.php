<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateShowIntentionalFailedDeliveriesRequest;

class ShowIntentionalFailedDeliveriesController extends Controller
{
    public function update(UpdateShowIntentionalFailedDeliveriesRequest $request)
    {
        $show = $request->boolean('show_intentional_failed_deliveries');

        user()->update([
            'show_intentional_failed_deliveries' => $show,
        ]);

        return back()->with([
            'flash' => $show
                ? 'Show Intentional Failed Deliveries Enabled Successfully'
                : 'Show Intentional Failed Deliveries Disabled Successfully',
        ]);
    }
}
