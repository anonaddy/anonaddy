<?php

namespace App\Http\Controllers\Auth;

use App\Actions\RegisterKeyStore;
use App\Models\WebauthnKey;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use LaravelWebauthn\Actions\RegisterKeyPrepare;
use LaravelWebauthn\Http\Controllers\WebauthnKeyController as ControllersWebauthnController;
use LaravelWebauthn\Services\Webauthn;
use Webauthn\PublicKeyCredentialCreationOptions;

class WebauthnController extends ControllersWebauthnController
{
    public function index()
    {
        return user()->webauthnKeys()->latest()->select(['id','name','enabled','created_at'])->get()->values();
    }

    /**
     * PublicKey Creation session name.
     *
     * @var string
     */
    private const SESSION_PUBLICKEY_CREATION = 'webauthn.publicKeyCreation';

    /**
     * Return the register data to attempt a Webauthn registration.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return RegisterViewResponse
     */
    public function create(Request $request)
    {
        $publicKey = $this->app[RegisterKeyPrepare::class]($request->user());

        $request->session()->put(Webauthn::SESSION_PUBLICKEY_CREATION, $publicKey);

        return view('vendor.webauthn.register')->with('publicKey', $publicKey);
    }

    /**
     * Validate and create the Webauthn request.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'register' => 'required|string',
            'name' => 'required|string|max:50'
        ]);

        try {
            $publicKey = $request->session()->pull(self::SESSION_PUBLICKEY_CREATION);
            if (! $publicKey instanceof PublicKeyCredentialCreationOptions) {
                throw new ModelNotFoundException(trans('webauthn::errors.create_data_not_found'));
            }

            /** @var \LaravelWebauthn\Models\WebauthnKey|null */
            $webauthnKey = $this->app[RegisterKeyStore::class](
                $request->user(),
                $publicKey,
                $request->input('register'),
                $request->input('name')
            );

            if ($webauthnKey !== null) {
                $request->session()->put(Webauthn::SESSION_WEBAUTHNID_CREATED, $webauthnKey->id);
            }

            user()->update([
                'two_factor_enabled' => false
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
     * @param WebauthnKey $webauthnKey
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    protected function redirectAfterSuccessRegister()
    {
        // If the user already has at least one key do not generate a new backup code.
        if (user()->webauthnKeys()->count() > 1) {
            return Redirect::intended('/settings');
        }

        user()->update([
            'two_factor_backup_code' => bcrypt($code = Str::random(40))
        ]);

        return Redirect::intended('/settings')->with(['backupCode' => $code]);
    }

    /**
     * Remove an existing Webauthn key.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $webauthnKeyId)
    {
        try {
            user()->webauthnKeys()
                ->findOrFail($webauthnKeyId)
                ->delete();

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
