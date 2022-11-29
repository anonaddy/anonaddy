<?php

namespace App\Http\Controllers;

class ShowUsernameController extends Controller
{
    public function index()
    {
        return view('usernames.index', [
            'usernames' => user()
                ->usernames()
                ->with('defaultRecipient:id,email')
                ->withCount('aliases')
                ->latest()
                ->get(),
        ]);
    }
}
