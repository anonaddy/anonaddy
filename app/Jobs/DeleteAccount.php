<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteAccount implements ShouldBeEncrypted, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

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
        $this->user->aliasRecipients()->delete();

        $sharedDomainAliases = $this->user->aliases()->withTrashed()->whereIn('domain', config('anonaddy.all_domains'));
        // Remove data from shared domain aliases
        $sharedDomainAliases->update([
            'extension' => null,
            'description' => null,
            'emails_forwarded' => 0,
            'emails_blocked' => 0,
            'emails_replied' => 0,
            'emails_sent' => 0,
            'last_forwarded' => null,
            'last_blocked' => null,
            'last_replied' => null,
            'last_sent' => null,
            'active' => false,
            'deleted_at' => now(), // Soft delete any aliases at shared domains
        ]);

        // Force delete any other aliases
        $this->user->aliases()->withTrashed()->whereNotIn('domain', config('anonaddy.all_domains'))->forceDelete();

        $this->user->recipients()->get()->each(function ($recipient) {
            // In order to fire deleting model event. With user to prevent lazy loading.
            $recipient->setRelation('user', $this->user);
            $recipient->delete();
        });
        $this->user->domains()->delete();
        $this->user->usernames()->get()->each->delete(); // In order to fire deleting model event.
        $this->user->tokens()->delete();
        $this->user->rules()->delete();
        $this->user->webauthnKeys()->delete();
        $this->user->failedDeliveries()->delete();
        $this->user->delete();
    }
}
