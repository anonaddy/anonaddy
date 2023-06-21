<?php

namespace App\Http\Controllers\Api;

use App\Helpers\GitVersionHelper as Version;
use App\Http\Controllers\Controller;

class AppVersionController extends Controller
{
    public function index()
    {
        $ver = Version::version();
        $parts = $ver->explode('.');

        return response()->json([
            'version' => $ver,
            'major' => isset($parts[0]) && $parts[0] !== '' ? (int) $parts[0] : 0,
            'minor' => isset($parts[1]) ? (int) $parts[1] : 0,
            'patch' => isset($parts[2]) ? (int) $parts[2] : 0,
        ]);
    }
}
