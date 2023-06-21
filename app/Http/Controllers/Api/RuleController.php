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
        $conditions = collect($request->conditions)->map(function ($condition) {
            return collect($condition)->only(['type', 'match', 'values']);
        });

        $actions = collect($request->actions)->map(function ($action) {
            return collect($action)->only(['type', 'value']);
        });

        $rule = user()->rules()->create([
            'name' => $request->name,
            'conditions' => $conditions,
            'actions' => $actions,
            'operator' => $request->operator,
            'forwards' => $request->forwards ?? false,
            'replies' => $request->replies ?? false,
            'sends' => $request->sends ?? false,
        ]);

        return new RuleResource($rule->refresh());
    }

    public function update(StoreRuleRequest $request, $id)
    {
        $rule = user()->rules()->findOrFail($id);

        $conditions = collect($request->conditions)->map(function ($condition) {
            return collect($condition)->only(['type', 'match', 'values']);
        });

        $actions = collect($request->actions)->map(function ($action) {
            return collect($action)->only(['type', 'value']);
        });

        $rule->update([
            'name' => $request->name,
            'conditions' => $conditions,
            'actions' => $actions,
            'operator' => $request->operator,
            'forwards' => $request->forwards ?? false,
            'replies' => $request->replies ?? false,
            'sends' => $request->sends ?? false,
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
