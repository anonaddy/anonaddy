<?php

namespace App\Http\Controllers;

class ShowAdditionalUsernameController extends Controller
{
    public function index()
    {
        return view('usernames.index', [
            'usernames' => user()->additionalUsernames()->latest()->get()
        ]);
    }
}
