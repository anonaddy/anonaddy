<?php

namespace App\Http\Controllers;

class ShowDomainController extends Controller
{
    public function index()
    {
        return view('domains.index', [
            'domains' => user()->domains()->with(['aliases', 'defaultRecipient'])->latest()->get()
        ]);
    }
}
