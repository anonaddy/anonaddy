<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\SubscribeToNewsletter;
use App\Recipient;
use App\Rules\NotBlacklisted;
use App\Rules\NotDeletedUsername;
use App\Rules\RegisterUniqueRecipient;
use App\User;
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
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'username' => [
                'required',
                'alpha_num',
                'max:20',
                'unique:users,username',
                'unique:additional_usernames,username',
                new NotBlacklisted,
                new NotDeletedUsername
            ],
            'email' => [
                'required',
                'email',
                'max:254',
                'confirmed',
                new RegisterUniqueRecipient
            ],
            'password' => ['required', 'min:8'],
            'newsletter' => ['nullable'],
            'terms' => ['required', 'accepted'],
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
     * @param  array  $data
     * @return \App\User
     */
    protected function create(array $data)
    {
        $userId = Uuid::uuid4();

        $recipient = Recipient::create([
            'email' => $data['email'],
            'user_id' => $userId
        ]);

        if (isset($data['newsletter'])) {
            SubscribeToNewsletter::dispatch($data['email']);
        }

        $twoFactor = app('pragmarx.google2fa');

        return User::create([
            'id' => $userId,
            'username' => $data['username'],
            'default_recipient_id' => $recipient->id,
            'password' => Hash::make($data['password']),
            'two_factor_secret' => $twoFactor->generateSecretKey()
        ]);
    }
}
