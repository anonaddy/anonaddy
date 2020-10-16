<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use PragmaRX\Version\Package\Facade as Version;

class AppVersionController extends Controller
{
    public function index()
    {
        return response()->json([
            'version' => Version::version(),
            'major' => (int) Version::major(),
            'minor' => (int) Version::minor(),
            'patch' => (int) Version::patch()
        ]);
    }
}
