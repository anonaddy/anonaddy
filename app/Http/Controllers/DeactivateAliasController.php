<?php

namespace App\Http\Controllers;

use App\Models\Alias;
use App\Notifications\AliasDeactivatedByUnsubscribeNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DeactivateAliasController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('signed');
        $this->middleware('throttle:6,1');
    }

    public function deactivate($id)
    {
        $alias = user()->aliases()->findOrFail($id);

        $alias->deactivate();

        Log::info('Email banner link deactivated alias: '.$alias->email.' ID: '.$id);

        return redirect()->route('aliases.edit', $alias->id)
            ->with(['flash' => 'Alias '.$alias->email.' deactivated successfully!']);
    }

    public function deactivatePost(Request $request, $id)
    {
        $alias = Alias::findOrFail($id);

        $wasActive = $alias->active;

        $alias->deactivate();

        Log::info('One-Click Unsubscribe deactivated alias: '.$alias->email.' ID: '.$id);

        if ($wasActive) {
            $cacheKey = "unsubscribe-deactivate-notify:{$alias->id}";

            if (! Cache::has($cacheKey)) {
                Cache::put($cacheKey, true, now()->addHour());

                $user = $alias->user;
                $user->notify(
                    (new AliasDeactivatedByUnsubscribeNotification(
                        $alias->email,
                        $alias->id,
                        $request->ip(),
                        $request->userAgent(),
                        now()->format('F j, g:i A (T)'),
                    ))
                );
            }
        }

        return response('');
    }
}
