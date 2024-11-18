<?php

namespace App\Http\Middleware;

use App\Helpers\GitVersionHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'layouts.app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Defines the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'flash' => $request->session()->get('flash', null),
            'user' => function () use ($request) {
                if (! $request->user()) {
                    return;
                }

                $user = $request->user();

                return [
                    'username' => $user->username,
                    'email' => $user->email,
                    'default_recipient_id' => $user->default_recipient_id,
                    'default_username_id' => $user->default_username_id,
                ];
            },
            'errorBags' => function () {
                return collect(optional(Session::get('errors'))->getBags() ?: [])->mapWithKeys(function ($bag, $key) {
                    return [$key => $bag->messages()];
                })->all();
            },
            'version' => GitVersionHelper::version(),
            'updateAvailable' => GitVersionHelper::updateAvailable(),
            'usesExternalAuthentication' => usesExternalAuthentication(),
        ]);
    }
}
