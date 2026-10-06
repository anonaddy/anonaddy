<?php

namespace App\Http\Controllers;

use App\Enums\Theme;
use App\Http\Requests\UpdateDarkModeRequest;

class DarkModeController extends Controller
{
    public function update(UpdateDarkModeRequest $request)
    {
        $theme = $request->enum('theme', Theme::class);

        user()->update(['theme' => $theme]);

        $message = match ($theme) {
            Theme::System => 'System theme enabled successfully',
            Theme::Light => 'Light theme enabled successfully',
            Theme::Dark => 'Dark theme enabled successfully',
        };

        return back()->with(['flash' => $message]);
    }
}
