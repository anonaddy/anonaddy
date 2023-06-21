<?php

namespace App\Console\Commands;

use App\Models\Recipient;
use App\Models\User;
use App\Models\Username;
use App\Rules\NotDeletedUsername;
use App\Rules\NotLocalRecipient;
use App\Rules\RegisterUniqueRecipient;
use Illuminate\Auth\Events\Registered;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Ramsey\Uuid\Uuid;

class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'anonaddy:create-user {username} {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new user';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $validator = Validator::make([
            'username' => $this->argument('username'),
            'email' => $this->argument('email'), ], [
                'username' => [
                    'required',
                    'regex:/^[a-zA-Z0-9]*$/',
                    'max:20',
                    'unique:usernames,username',
                    new NotDeletedUsername(),
                ],
                'email' => [
                    'required',
                    'email:rfc,dns',
                    'max:254',
                    new RegisterUniqueRecipient(),
                    new NotLocalRecipient(),
                ],
            ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            foreach ($errors->all() as $message) {
                $this->error($message);
            }

            return 1;
        }
        $userId = Uuid::uuid4();

        $recipient = Recipient::create([
            'email' => $this->argument('email'),
            'user_id' => $userId,
        ]);

        $username = Username::create([
            'username' => $this->argument('username'),
            'user_id' => $userId,
        ]);

        $twoFactor = app('pragmarx.google2fa');

        $user = User::create([
            'id' => $userId,
            'default_username_id' => $username->id,
            'default_recipient_id' => $recipient->id,
            'password' => Hash::make($userId),
            'two_factor_secret' => $twoFactor->generateSecretKey(),
        ]);

        event(new Registered($user));

        $this->info('Created user: "'.$user->username.'" with user_id: "'.$user->id.'"');
        $this->info('This user can now reset their password (the default password is their user_id)');

        return 0;
    }
}
