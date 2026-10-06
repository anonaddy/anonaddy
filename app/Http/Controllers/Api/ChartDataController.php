<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class ChartDataController extends Controller
{
    public function index()
    {
        $emptyDay = [
            'forwards' => 0,
            'replies' => 0,
            'sends' => 0,
            'inbound_rejections' => 0,
            'inbound_quarantined' => 0,
            'outbound_bounces' => 0,
        ];

        $from = now()->subDays(6)->startOfDay();

        $outboundMessages = user()->outboundMessages()
            ->select(['user_id', 'email_type', 'created_at'])
            ->where('created_at', '>=', $from)
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

        $failedDeliveries = user()->failedDeliveries()
            ->visibleFor(user())
            ->select(['user_id', 'email_type', 'quarantined', 'ir_dedupe_key', 'created_at'])
            ->where('created_at', '>=', $from)
            ->get()
            ->groupBy(function ($failedDelivery) {
                return $failedDelivery->created_at->format('l');
            })
            ->map(function ($group) {
                return [
                    'inbound_rejections' => $group->where('type', 'inbound')->count(),
                    'inbound_quarantined' => $group->where('type', 'inbound_quarantined')->count(),
                    'outbound_bounces' => $group->where('type', 'outbound')->count(),
                ];
            });

        $data = collect(range(6, 0))->mapWithKeys(function (int $daysAgo) use ($outboundMessages, $failedDeliveries, $emptyDay) {
            $label = now()->subDays($daysAgo)->format('l');

            return [$label => array_merge(
                $emptyDay,
                $outboundMessages->get($label, []),
                $failedDeliveries->get($label, [])
            )];
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
            'inboundRejectionsData' => $data->pluck('inbound_rejections'),
            'inboundQuarantinedData' => $data->pluck('inbound_quarantined'),
            'outboundBouncesData' => $data->pluck('outbound_bounces'),
            'failedDeliveriesTotal' => (int) $data->sum('inbound_rejections') + (int) $data->sum('inbound_quarantined') + (int) $data->sum('outbound_bounces'),
        ]);
    }
}
