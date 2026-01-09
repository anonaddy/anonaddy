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

class RemoveUsernameExternalId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'anonaddy:remove-username-externalid {username}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes the externalid of an username for logging in using Proxy authentication';

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
            'username' => $this->argument('username')], [
                'username' => [
                    'required',
                    'exists:usernames,username'
                ],
            ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            foreach ($errors->all() as $message) {
                $this->error($message);
            }

            return 1;
        }

        $username = Username::where('username', $this->argument('username'))->first();

        $username->external_id = null;
        $username->save();

        $this->info('Externalid of username: "'. $username->username . '" is removed');
        return 0;
    }
}
