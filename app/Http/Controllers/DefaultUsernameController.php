<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateDefaultUsernameRequest;

class DefaultUsernameController extends Controller
{
    public function update(UpdateDefaultUsernameRequest $request)
    {
        $username = user()->usernames()->findOrFail($request->id);

        // Ensure username can be used to login
        $username->allowLogin();

        user()->update(['default_username_id' => $username->id]);

        return response()->json([
            'success' => true,
        ]);
    }
}
