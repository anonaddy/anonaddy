<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportAliasesRequest;
use App\Imports\AliasesImport;
use Illuminate\Support\Facades\App;
use Maatwebsite\Excel\HeadingRowImport;

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

    public function import(ImportAliasesRequest $request)
    {
        try {
            $import = new AliasesImport(user());

            $headings = (new HeadingRowImport)->toCollection($request->file('aliases_import'))->flatten();

            // Validate the heading row
            if (($headings->diff(['alias', 'description', 'recipients'])->count() || $headings->count() !== 3) && ! App::environment('testing')) {
                return back()->withErrors(['aliases_import' => 'The aliases import file has invalid headers, please use the template provided above.']);
            }

            $import->queue($request->file('aliases_import'));
        } catch (\Exception $e) {
            report($e);
        }

        return back()->with(['status' => 'File uploaded successfully, your aliases are being imported']);
    }
}
