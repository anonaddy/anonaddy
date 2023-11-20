<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class ShowDashboardController extends Controller
{
    public function index()
    {
        $totals = user()
            ->aliases()
            ->withTrashed()
            ->toBase()
            ->selectRaw('ifnull(count(id),0) as total')
            ->selectRaw('ifnull(sum(active=1),0) as active')
            ->selectRaw('ifnull(sum(CASE WHEN active=0 AND deleted_at IS NULL THEN 1 END),0) as inactive')
            ->selectRaw('ifnull(sum(CASE WHEN deleted_at IS NOT NULL THEN 1 END),0) as deleted')
            ->selectRaw('ifnull(sum(emails_forwarded),0) as forwarded')
            ->selectRaw('ifnull(sum(emails_blocked),0) as blocked')
            ->selectRaw('ifnull(sum(emails_replied),0) as replies')
            ->selectRaw('ifnull(sum(emails_sent),0) as sent')
            ->first();

        return Inertia::render('Dashboard/Index', [
            'totals' => $totals,
            'bandwidthMb' => user()->bandwidthMb,
            'bandwidthLimit' => user()->getBandwidthLimitMb(),
            'month' => now()->format('F'),
            'aliases' => user()->activeSharedDomainAliases()->count(),
            'recipients' => user()->recipients()->count(),
            'usernames' => user()->usernames()->count(),
            'domains' => user()->domains()->count(),
            'rules' => user()->rules()->count(),
        ]);
    }
}
