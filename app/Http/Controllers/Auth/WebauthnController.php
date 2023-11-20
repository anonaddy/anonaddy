<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use LaravelWebauthn\Actions\PrepareCreationData;
use LaravelWebauthn\Actions\ValidateKeyCreation;
use LaravelWebauthn\Contracts\DestroyResponse;
use LaravelWebauthn\Contracts\RegisterSuccessResponse;
use LaravelWebauthn\Contracts\RegisterViewResponse;
use LaravelWebauthn\Facades\Webauthn;
use LaravelWebauthn\Http\Controllers\WebauthnKeyController as ControllersWebauthnController;
use LaravelWebauthn\Http\Requests\WebauthnRegisterRequest;

class WebauthnController extends ControllersWebauthnController
{
    public function index()
    {
        return user()->webauthnKeys()->latest()->select(['id', 'name', 'enabled', 'created_at'])->get()->values();
    }

    /**
     * Return the register data to attempt a Webauthn registration.
     */
    /* public function create(Request $request): RegisterViewResponse
    {
        $publicKey = app(PrepareCreationData::class)($request->user());

        return app(RegisterViewResponse::class)
            ->setPublicKey($request, $publicKey);
    } */

    /**
     * Validate and create the Webauthn request.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function store(WebauthnRegisterRequest $request): RegisterSuccessResponse
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'password' => 'required|string|current_password',
        ]);

        $webauthnKey = app(ValidateKeyCreation::class)(
            $request->user(),
            $request->only(['id', 'rawId', 'response', 'type']),
            $request->input('name')
        );

        user()->update([
            'two_factor_enabled' => false,
        ]);

        return app(RegisterSuccessResponse::class)
            ->setWebauthnKey($request, $webauthnKey);
    }

    /**
     * Remove an existing Webauthn key.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $webauthnKeyId): DestroyResponse
    {
        $request->validate([
            'current' => 'required|string|current_password',
        ]);

        user()->webauthnKeys()
            ->findOrFail($webauthnKeyId)
            ->delete();

        // Using vendor Facade to ensure disabled keys are included
        if (! Webauthn::hasKey(user())) {
            // Remove session value when last key is deleted
            Webauthn::logout();
        }

        return app(DestroyResponse::class);
    }

    public function delete()
    {
        return abort(404);
    }
}
