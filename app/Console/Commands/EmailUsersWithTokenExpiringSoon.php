<?php

namespace App\Console\Commands;

use App\Mail\TokenExpiringSoon;
use App\Models\User;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class EmailUsersWithTokenExpiringSoon extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'anonaddy:email-users-with-token-expiring-soon';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send an email to users who have an API token that is expiring soon';

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
        User::whereHas('tokens', function ($query) {
            $query->whereDate('expires_at', now()->addWeek())
                ->where('revoked', false);
        })
        ->get()
        ->each(function (User $user) {
            $this->sendTokenExpiringSoonMail($user);
        });
    }

    protected function sendTokenExpiringSoonMail(User $user)
    {
        try {
            Mail::to($user->email)->send(new TokenExpiringSoon($user));
        } catch (Exception $exception) {
            $this->error("exception when sending mail to user: {$user->username}", $exception);
            report($exception);
        }
    }
}
