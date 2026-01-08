<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\NotBlacklisted;
use App\Rules\NotDeletedUsername;
use App\Rules\NotLocalRecipient;
use App\Rules\RegisterUniqueRecipient;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;


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
        // Validate captcha separately first to prevent username enumeration
        if (! App::environment('testing')) {
            $validator = Validator::make($data, [
                'captcha' => [
                    'required',
                    'captcha',
                ],
            ], [
                'captcha.captcha' => 'The text entered was incorrect, please try again.',
            ]);

            if ($validator->fails()) {
                return $validator;
            }
        }

        return Validator::make($data, [
            'username' => [
                'bail',
                'required',
                'regex:/^[a-zA-Z0-9]*$/',
                'max:20',
                'unique:usernames,username',
                new NotBlacklisted,
                new NotDeletedUsername,
            ],
            'email' => [
                'bail',
                'required',
                'string',
                'ascii',
                App::environment(['local', 'testing']) ? 'email:rfc' : 'email:rfc,dns',
                'max:254',
                'confirmed',
                new RegisterUniqueRecipient,
                new NotLocalRecipient,
            ],
            'password' => ['required', Password::defaults()],
        ], [
            'username.regex' => 'Your username can only contain letters and numbers.',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        return createUser($data['username'], $data['email'], $data['password']);
    }
}
