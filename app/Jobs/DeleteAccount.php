<?php

namespace App\Jobs;

use App\DeletedUsername;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteAccount implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        DeletedUsername::create(['username' => $this->user->username]);

        $this->user->aliasRecipients()->delete();
        $this->user->aliases()->forceDelete();
        $this->user->recipients()->get()->each->delete(); // In order to fire deleting model event.
        $this->user->domains()->delete();
        $this->user->additionalUsernames()->get()->each->delete(); // In order to fire deleting model event.
        $this->user->tokens()->delete();
        $this->user->delete();
    }
}
