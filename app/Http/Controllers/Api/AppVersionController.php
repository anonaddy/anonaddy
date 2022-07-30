<?php

namespace App\Http\Controllers\Api;

use App\Helpers\GitVersionHelper as Version;
use App\Http\Controllers\Controller;

class AppVersionController extends Controller
{
    public function index()
    {
        $parts = str(Version::version())->explode('.');

        return response()->json([
            'version' => Version::version(),
            'major' => (int) isset($parts[0]) ? $parts[0] : null,
            'minor' => (int) isset($parts[1]) ? $parts[1] : null,
            'patch' => (int) isset($parts[2]) ? $parts[2] : null,
        ]);
    }
}
