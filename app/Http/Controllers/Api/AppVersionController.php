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
            'major' => (int) $parts[0],
            'minor' => (int) $parts[1],
            'patch' => (int) $parts[2]
        ]);
    }
}
