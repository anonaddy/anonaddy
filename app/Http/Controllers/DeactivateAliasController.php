<?php

namespace App\Http\Controllers;

class DeactivateAliasController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('signed');
    }

    public function deactivate($id)
    {
        $alias = user()->aliases()->findOrFail($id);

        $alias->deactivate();

        return redirect()->route('aliases.index')
            ->with(['status' => 'Alias ' . $alias->email . ' deactivated successfully!']);
    }
}
