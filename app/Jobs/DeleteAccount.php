<?php

namespace App\Jobs;

use App\Models\DeletedUsername;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteAccount implements ShouldQueue, ShouldBeEncrypted
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

        $sharedDomainAliases = $this->user->aliases()->whereIn('domain', config('anonaddy.all_domains'));
        // Remove data from shared domain aliases
        $sharedDomainAliases->update([
            'extension' => null,
            'description' => null,
            'emails_forwarded' => 0,
            'emails_blocked' => 0,
            'emails_replied' => 0,
            'emails_sent' => 0,
        ]);
        // Soft delete any aliases at shared domains
        $sharedDomainAliases->delete();
        // Force delete any other aliases
        $this->user->aliases()->whereNotIn('domain', config('anonaddy.all_domains'))->forceDelete();

        $this->user->recipients()->get()->each->delete(); // In order to fire deleting model event.
        $this->user->domains()->delete();
        $this->user->additionalUsernames()->get()->each->delete(); // In order to fire deleting model event.
        $this->user->tokens()->delete();
        $this->user->rules()->delete();
        $this->user->webauthnKeys()->delete();
        $this->user->delete();
    }
}
