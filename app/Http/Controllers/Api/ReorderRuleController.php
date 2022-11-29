<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReorderRuleRequest;
use App\Models\Rule;

class ReorderRuleController extends Controller
{
    public function store(StoreReorderRuleRequest $request)
    {
        collect($request->ids)->each(function ($id, $key) {
            $rule = Rule::findOrFail($id);

            $rule->update([
                'order' => $key,
            ]);
        });

        return response('', 200);
    }
}
