<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Recipient;
use App\Models\User;
use App\Models\Username;
use App\Rules\NotBlacklisted;
use App\Rules\NotDeletedUsername;
use App\Rules\NotLocalRecipient;
use App\Rules\RegisterUniqueRecipient;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Ramsey\Uuid\Uuid;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'username' => [
                'required',
                'regex:/^[a-zA-Z0-9]*$/',
                'max:20',
                'unique:usernames,username',
                new NotBlacklisted(),
                new NotDeletedUsername(),
            ],
            'email' => [
                'required',
                'email:rfc,dns',
                'max:254',
                'confirmed',
                new RegisterUniqueRecipient(),
                new NotLocalRecipient(),
            ],
            'password' => ['required', 'min:8'],
        ], [
            'captcha.captcha' => 'The text entered was incorrect, please try again.',
        ])
            ->sometimes('captcha', 'required|captcha', function () {
                return ! App::environment('testing');
            });
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        $userId = Uuid::uuid4();

        $recipient = Recipient::create([
            'email' => $data['email'],
            'user_id' => $userId,
        ]);

        $username = Username::create([
            'username' => $data['username'],
            'user_id' => $userId,
        ]);

        $twoFactor = app('pragmarx.google2fa');

        return User::create([
            'id' => $userId,
            'default_username_id' => $username->id,
            'default_recipient_id' => $recipient->id,
            'password' => Hash::make($data['password']),
            'two_factor_secret' => $twoFactor->generateSecretKey(),
        ]);
    }
}
