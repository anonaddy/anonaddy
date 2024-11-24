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

class SetUsernameExternalId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'anonaddy:set-username-externalid {username} {externalid}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the externalid of an username for logging in using Proxy authentication';

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
            'external_id' => $this->argument('external_id')], [
                'username' => [
                    'required',
                    'exists:usernames,username'
                ],
                'external_id' => [
                    'required',
                    'unique:usernames,external_id'
                ],
            ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            foreach ($errors->all() as $message) {
                $this->error($message);
            }

            return 1;
        }

        $username = Username::select(['user_id', 'username', 'can_login', ])
            ->where('username', $this->argument('username'))
            ->first();

        $username->external_id = $this->argument('external_id');
        $username->can_login = true;
        $username->save();

        $username->user->default_username = $username;
        $username->user->save();

        $this->info('Username: "'. $username->username . '" set as external with id : "'. $username->external_id .'"');
        return 0;
    }
}
