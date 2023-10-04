<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class ChartDataController extends Controller
{
    public function index()
    {
        $outboundMessages = user()->outboundMessages()
            ->select(['user_id', 'email_type', 'created_at'])
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->get()
            ->groupBy(function ($outboundMessage) {
                return $outboundMessage->created_at->format('l');
            })
            ->map(function ($group) {
                return [
                    'forwards' => $group->where('email_type', 'F')->count(),
                    'replies' => $group->where('email_type', 'R')->count(),
                    'sends' => $group->where('email_type', 'S')->count(),
                ];
            });

        $days = [
            'Sunday',
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
            'Friday',
            'Saturday',
        ];

        $today = date('w'); // 0 Sunday

        // Get the days until today including today
        $previous = array_slice($days, 0, $today + 1);

        // Get remaining days in week
        $coming = array_slice($days, $today + 1);

        $data = collect(array_merge($coming, $previous))->mapWithKeys(function ($day) use ($outboundMessages) {
            return [$day => $outboundMessages->get($day, ['forwards' => 0, 'replies' => 0, 'sends' => 0])];
        });

        $outboundMessageTotals = [
            $outboundMessages->sum('forwards'),
            $outboundMessages->sum('replies'),
            $outboundMessages->sum('sends'),
        ];

        return response()->json([
            'forwardsData' => $data->pluck('forwards'),
            'repliesData' => $data->pluck('replies'),
            'sendsData' => $data->pluck('sends'),
            'labels' => $data->keys(),
            'outboundMessageTotals' => $outboundMessageTotals,
        ]);
    }
}
