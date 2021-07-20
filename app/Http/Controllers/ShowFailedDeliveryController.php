<?php

namespace App\Http\Controllers;

class ShowFailedDeliveryController extends Controller
{
    public function index()
    {
        return view('failed_deliveries.index', [
            'failedDeliveries' => user()
                ->failedDeliveries()
                ->with(['recipient:id,email','alias:id,email'])
                ->select(['alias_id','bounce_type','code','created_at','id','recipient_id','remote_mta','sender'])
                ->latest()
                ->get()
        ]);
    }
}
