<?php

namespace App\Http\Controllers;

class ShowRuleController extends Controller
{
    public function index()
    {
        return view('rules.index', [
            'rules' => user()
                ->rules()
                ->orderBy('order')
                ->get(),
        ]);
    }
}
