<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Recipient;
use App\Models\Username;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Ramsey\Uuid\Uuid;
use Carbon\Carbon;

class AuthentikController extends Controller
{
    
    public function callback()
    {

        try {
            $authentikUser = Socialite::driver('authentik')->user();
        }
        catch (\Laravel\Socialite\Two\InvalidStateException $e) {
            return redirect()->route('login');
        }
        
        $user = User::where('authentik_id', $authentikUser->id)->first();
        
        if ($user) {

            $user->update([
                'authentik_token' => $authentikUser->token,
                'authentik_refresh_token' => $authentikUser->refreshToken,
            ]);

            if (in_array(config('services.authentik.group'), $authentikUser->user['groups'])) {
                
                Auth::login($user);

                return redirect()->route('aliases.index');

            } else {

                abort(403);

            }

        } else {

            if (in_array(config('services.authentik.group'), $authentikUser->user['groups'])) {

                $userId = Uuid::uuid4();

                $recipient = Recipient::create([
                    'email' => $authentikUser->email,
                    'user_id' => $userId,
                    'email_verified_at' => Carbon::now(),
                ]);

                $username = Username::create([
                    'username' => $authentikUser->user['preferred_username'],
                    'user_id' => $userId,
                ]);

                User::create([
                    'id' => $userId,
                    'default_username_id' => $username->id,
                    'default_recipient_id' => $recipient->id,
                    'authentik_id' => $authentikUser->id,
                    'authentik_token' => $authentikUser->token,
                    'authentik_refresh_token' => $authentikUser->refreshToken,
                ]);

                return redirect()->route('aliases.index');

            } else {

                abort(403);

            }
        }

        

    }


    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect(config('services.authentik.logout'));

    }
}
