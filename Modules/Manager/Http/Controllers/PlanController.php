<?php

namespace Modules\Manager\Http\Controllers;

use Modules\Manager\Entities\Feature;
use Modules\Manager\Entities\Plan;
use Illuminate\Http\Request;
use Modules\Manager\Services\EntitlementWriter;
use Illuminate\Routing\Controller;

class PlanController extends Controller
{
    public function index()
    {
        return view('manager::plans.index', [
            'title' => 'Planlar',
            'plans' => Plan::query()->withCount(['features'])->withCount('subscriptions')->orderBy('sort_order')->get(),
        ]);
    }

    public function edit(Plan $plan)
    {
        return view('manager::plans.form', [
            'title' => 'Plan: '.$plan->name,
            'plan' => $plan,
            'groups' => Feature::query()->orderBy('sort_order')->get()->groupBy('group'),
            'values' => $plan->features->pluck('pivot.value', 'id')->all(),
        ]);
    }

    public function update(Request $request, Plan $plan, EntitlementWriter $writer)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'billing_cycle' => ['required', 'in:monthly,yearly'],
            'is_active' => ['nullable', 'boolean'],
            'features' => ['nullable', 'array'],
        ]);

        $plan->update([
            'name' => $data['name'],
            'price' => $data['price'],
            'billing_cycle' => $data['billing_cycle'],
            'is_active' => $request->boolean('is_active'),
        ]);

        $sync = [];
        foreach ((array) $request->input('features', []) as $featureId => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $sync[$featureId] = ['value' => (string) $value];
        }
        $plan->features()->sync($sync);

        // Push the updated entitlements to every owner on this plan.
        $plan->subscriptions()->with('siteOwner.domains')->get()
            ->pluck('siteOwner')->filter()->unique('id')
            ->each(fn ($owner) => $writer->push($owner));

        return back()->with('status', __('Plan yeniləndi.'));
    }
}
