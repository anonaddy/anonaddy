<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use LaravelWebauthn\Http\Responses\RegisterSuccessResponse as RegisterSuccessResponseBase;

class RegisterSuccessResponse extends RegisterSuccessResponseBase
{
    public function toResponse($request)
    {
        // If the user already has at least one key do not generate a new backup code.
        if (user()->webauthnKeys()->count() > 1) {
            return Redirect::intended('/settings/security');
        }

        user()->update([
            'two_factor_backup_code' => bcrypt($code = Str::random(40)),
        ]);

        return Redirect::intended('/settings/security')->with(['backupCode' => $code]);
    }
}
