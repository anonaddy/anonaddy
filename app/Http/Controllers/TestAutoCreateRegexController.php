<?php

namespace App\Http\Controllers;

use App\Http\Requests\TestAutoCreateRegexRequest;

class TestAutoCreateRegexController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('throttle:60,1');
    }

    public function index(TestAutoCreateRegexRequest $request)
    {
        $query = $request->resource === 'username' ? user()->usernames() : user()->domains();

        return response()->json([
            'success' => $query
                ->where('id', $request->id)
                ->whereNotNull('auto_create_regex')
                ->whereRaw('? REGEXP auto_create_regex', [$request->local_part])
                ->exists(),
        ]);
    }
}
