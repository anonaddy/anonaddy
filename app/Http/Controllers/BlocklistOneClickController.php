<?php

namespace App\Http\Controllers;

use App\Models\Alias;
use App\Models\BlockedSender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BlocklistOneClickController extends Controller
{
    public function __construct()
    {
        $this->middleware('signed');
        $this->middleware('throttle:6,1');
    }

    public function blockEmailPost(Request $request, string $alias)
    {
        $alias = Alias::findOrFail($alias);

        // Only the signed query string can be trusted, a POST body field would override it in validate()
        $validated = Validator::make($request->query(), [
            'email' => ['required', 'string', 'email', 'max:253'],
        ])->validate();

        $value = strtolower(trim($validated['email']));

        $alias->user->blockedSenders()->firstOrCreate(
            ['type' => 'email', 'value' => $value]
        );

        Log::info('One-Click Unsubscribe blocked email: '.$value.' for alias: '.$alias->email.' ID: '.$alias->id);

        return response('');
    }

    public function blockDomainPost(Request $request, string $alias)
    {
        $alias = Alias::findOrFail($alias);

        // Only the signed query string can be trusted, a POST body field would override it in validate()
        $validated = Validator::make($request->query(), [
            'domain' => ['required', 'string', 'max:253', 'regex:/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i'],
        ])->validate();

        $value = strtolower(trim($validated['domain']));

        if (BlockedSender::isProtectedAliasDomain($value)) {
            return response('', 422);
        }

        $alias->user->blockedSenders()->firstOrCreate(
            ['type' => 'domain', 'value' => $value]
        );

        Log::info('One-Click Unsubscribe blocked domain: '.$value.' for alias: '.$alias->email.' ID: '.$alias->id);

        return response('');
    }
}
