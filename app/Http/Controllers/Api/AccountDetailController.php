<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;

class AccountDetailController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => new UserResource(user()),
        ]);
    }
}
