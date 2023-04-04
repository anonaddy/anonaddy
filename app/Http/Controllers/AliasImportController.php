<?php

namespace App\Http\Controllers;

use App\Imports\AliasesImport;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\File;

class AliasImportController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('throttle:1,1'); // Limit to 1 upload per minute
    }

    public function import(Request $request)
    {
        // Validate
        $request->validate([
            'aliases_import' => [
                'required',
                File::types(['csv'])->max(5 * 1024) // 5MB
            ],
        ]);

        try {
            $import = new AliasesImport(user());
            $import->queue($request->file('aliases_import'));
        } catch (\Exception $e) {
            report($e);
        }

        return back()->with(['status' => 'File uploaded successfully, your aliases are being imported']);
    }
}
