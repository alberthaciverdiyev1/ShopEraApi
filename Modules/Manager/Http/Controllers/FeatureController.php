<?php

namespace Modules\Manager\Http\Controllers;

use Modules\Manager\Entities\Feature;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class FeatureController extends Controller
{
    public function index(Request $request)
    {
        $rows = Feature::query()->orderBy('sort_order')->paginate(50);

        if ($request->header('HX-Request')) {
            return view('manager::features._table', ['rows' => $rows]);
        }

        return view('manager::features.index', ['title' => 'Feature-lar', 'rows' => $rows]);
    }

    public function create()
    {
        return view('manager::features._form', ['feature' => null]);
    }

    public function edit(Feature $feature)
    {
        return view('manager::features._form', ['feature' => $feature]);
    }

    public function store(Request $request)
    {
        $validator = validator($request->all(), $this->rules());

        if ($validator->fails()) {
            return view('manager::features._form', ['feature' => null])->withErrors($validator);
        }

        Feature::query()->create($validator->validated());

        return $this->tableResponse();
    }

    public function update(Request $request, Feature $feature)
    {
        $validator = validator($request->all(), $this->rules($feature));

        if ($validator->fails()) {
            return view('manager::features._form', ['feature' => $feature])->withErrors($validator);
        }

        $feature->update($validator->validated());

        return $this->tableResponse();
    }

    public function destroy(Feature $feature)
    {
        $feature->delete();

        return $this->tableResponse();
    }

    private function rules(?Feature $feature = null): array
    {
        return [
            'key' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9_]+$/', 'unique:control.features,key'.($feature ? ','.$feature->id : '')],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:bool,limit'],
            'default_value' => ['nullable', 'string', 'max:255'],
            'group' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
        ];
    }

    private function tableResponse()
    {
        $rows = Feature::query()->orderBy('sort_order')->paginate(50);

        return response()
            ->view('manager::features._table', ['rows' => $rows])
            ->header('HX-Trigger', json_encode([
                'toast' => ['type' => 'success', 'message' => 'Yadda saxlanıldı.'],
                'modal:close' => true,
            ]));
    }
}
