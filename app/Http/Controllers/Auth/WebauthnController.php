<?php

namespace App\Http\Controllers\Auth;

use App\Facades\Webauthn as WebauthnFacade;
use App\Models\WebauthnKey;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use LaravelWebauthn\Actions\PrepareCreationData;
use LaravelWebauthn\Actions\ValidateKeyCreation;
use LaravelWebauthn\Contracts\RegisterViewResponse;
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
     *
     * @return RegisterViewResponse
     */
    public function create(Request $request)
    {
        $publicKey = app(PrepareCreationData::class)($request->user());

        return app(RegisterViewResponse::class)
            ->setPublicKey($request, $publicKey);
    }

    /**
     * Validate and create the Webauthn request.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function store(WebauthnRegisterRequest $request)
    {
        $request->validate([
            'name' => 'required|string|max:50',
        ]);

        try {
            app(ValidateKeyCreation::class)(
                $request->user(),
                $request->only(['id', 'rawId', 'response', 'type']),
                $request->input('name')
            );

            user()->update([
                'two_factor_enabled' => false,
            ]);

            return $this->redirectAfterSuccessRegister();
        } catch (\Exception $e) {
            return Response::json([
                'error' => [
                    'message' => $e->getMessage(),
                ],
            ], 403);
        }
    }

    /**
     * Return the redirect destination after a successfull register.
     *
     * @param  WebauthnKey  $webauthnKey
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    protected function redirectAfterSuccessRegister()
    {
        // If the user already has at least one key do not generate a new backup code.
        if (user()->webauthnKeys()->count() > 1) {
            return Redirect::intended('/settings');
        }

        user()->update([
            'two_factor_backup_code' => bcrypt($code = Str::random(40)),
        ]);

        return Redirect::intended('/settings')->with(['backupCode' => $code]);
    }

    /**
     * Remove an existing Webauthn key.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $webauthnKeyId)
    {
        try {
            user()->webauthnKeys()
                ->findOrFail($webauthnKeyId)
                ->delete();

            if (! WebauthnFacade::hasKey(user())) {
                WebauthnFacade::logout();
            }

            return Response::json([
                'deleted' => true,
                'id' => $webauthnKeyId,
            ]);
        } catch (ModelNotFoundException $e) {
            return Response::json([
                'error' => [
                    'message' => trans('webauthn::errors.object_not_found'),
                ],
            ], 404);
        }
    }
}
