<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class DownloadableFailedDeliveryController extends Controller
{
    public function index($id)
    {
        $failedDelivery = user()->failedDeliveries()->findOrFail($id);

        if (! $failedDelivery->is_stored) {
            abort(404);
        }

        if (! Storage::disk('local')->exists($failedDelivery->id.'.eml')) {
            abort(404);
        }

        return Storage::disk('local')->download($failedDelivery->id.'.eml');
    }
}
