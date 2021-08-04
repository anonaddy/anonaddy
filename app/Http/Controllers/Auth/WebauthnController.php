<?php

namespace App\Http\Controllers\Auth;

use App\Facades\Webauthn;
use App\Models\WebauthnKey;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use LaravelWebauthn\Http\Controllers\WebauthnController as ControllersWebauthnController;
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
     * Validate and create the Webauthn request.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required|max:50',
            'register' => 'required',
        ]);

        try {
            $publicKey = $request->session()->pull(self::SESSION_PUBLICKEY_CREATION);
            if (! $publicKey instanceof PublicKeyCredentialCreationOptions) {
                throw new ModelNotFoundException(trans('webauthn::errors.create_data_not_found'));
            }

            $webauthnKey = Webauthn::doRegister(
                $request->user(),
                $publicKey,
                $this->input($request, 'register'),
                $this->input($request, 'name')
            );

            user()->update([
                'two_factor_enabled' => false
            ]);

            return $this->redirectAfterSuccessRegister($webauthnKey);
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
    protected function redirectAfterSuccessRegister($webauthnKey)
    {
        if ($this->config->get('webauthn.register.postSuccessRedirectRoute', '') !== '') {

            // If the user already has at least one key do not generate a new backup code.
            if (user()->webauthnKeys()->count() > 1) {
                return Redirect::intended($this->config->get('webauthn.register.postSuccessRedirectRoute'));
            }

            user()->update([
                'two_factor_backup_code' => bcrypt($code = Str::random(40))
            ]);

            return Redirect::intended($this->config->get('webauthn.register.postSuccessRedirectRoute'))->with(['backupCode' => $code]);
        } else {
            return Response::json([
                'result' => true,
                'id' => $webauthnKey->id,
                'object' => 'webauthnKey',
                'name' => $webauthnKey->name,
                'counter' => $webauthnKey->counter,
            ], 201);
        }
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

    /**
     * Retrieve the input with a string result.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $name
     * @param string $default
     * @return string
     */
    private function input(Request $request, string $name, string $default = ''): string
    {
        $result = $request->input($name);

        return is_string($result) ? $result : $default;
    }
}
