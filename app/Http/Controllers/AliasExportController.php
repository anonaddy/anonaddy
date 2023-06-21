<?php

namespace App\Http\Controllers;

use App\Exports\AliasesExport;
use Maatwebsite\Excel\Facades\Excel;

class AliasExportController extends Controller
{
    public function export()
    {
        return Excel::download(new AliasesExport(), 'aliases-'.now()->toDateString().'.csv');
    }
}
