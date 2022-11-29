<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class DomainOptionController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => user()->domainOptions(),
            'defaultAliasDomain' => user()->default_alias_domain,
            'defaultAliasFormat' => user()->default_alias_format,
        ]);
    }
}
