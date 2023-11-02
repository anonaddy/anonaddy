<?php

namespace App\Http\Controllers;

use App\Enums\LoginRedirect;
use App\Http\Requests\UpdateLoginRedirectRequest;

class LoginRedirectController extends Controller
{
    public function update(UpdateLoginRedirectRequest $request)
    {
        user()->login_redirect = LoginRedirect::from($request->redirect);
        user()->save();

        return back()->with(['flash' => 'Login Redirect Updated Successfully']);
    }
}
