<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RuleResource;
use Illuminate\Http\Request;

class ActiveRuleController extends Controller
{
    public function store(Request $request)
    {
        $request->validate(['id' => 'required|string']);

        $rule = user()->rules()->findOrFail($request->id);

        $rule->activate();

        return new RuleResource($rule);
    }

    public function destroy($id)
    {
        $rule = user()->rules()->findOrFail($id);

        $rule->deactivate();

        return response('', 204);
    }
}
