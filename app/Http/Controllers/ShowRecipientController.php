<?php

namespace App\Http\Controllers;

class ShowRecipientController extends Controller
{
    public function index()
    {
        $recipients = user()->recipients()->with('aliases')->latest()->get();

        $count = $recipients->count();

        $recipients->each(function ($item, $key) use ($count) {
            $item['key'] = $count - $key;
        });

        return view('recipients.index', [
            'recipients' => $recipients,
            'aliasesUsingDefault' => user()->aliasesUsingDefault
        ]);
    }
}
