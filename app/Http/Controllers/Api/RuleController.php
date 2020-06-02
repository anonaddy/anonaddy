<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRuleRequest;
use App\Http\Resources\RuleResource;

class RuleController extends Controller
{
    public function index()
    {
        return RuleResource::collection(user()->rules()->orderBy('order')->get());
    }

    public function show($id)
    {
        $rule = user()->rules()->findOrFail($id);

        return new RuleResource($rule);
    }

    public function store(StoreRuleRequest $request)
    {
        $rule = user()->rules()->create([
            'name' => $request->name,
            'conditions' => $request->conditions,
            'actions' => $request->actions,
            'operator' => $request->operator
        ]);

        return new RuleResource($rule->refresh());
    }

    public function update(StoreRuleRequest $request, $id)
    {
        $rule = user()->rules()->findOrFail($id);

        $rule->update([
            'name' => $request->name,
            'conditions' => $request->conditions,
            'actions' => $request->actions,
            'operator' => $request->operator
        ]);

        return new RuleResource($rule->refresh());
    }

    public function destroy($id)
    {
        $rule = user()->rules()->findOrFail($id);

        $rule->delete();

        return response('', 204);
    }
}
