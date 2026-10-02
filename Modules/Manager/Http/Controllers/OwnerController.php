<?php

namespace Modules\Manager\Http\Controllers;

use Modules\Manager\Entities\Domain;
use Modules\Manager\Entities\Feature;
use Modules\Manager\Entities\OwnerFeature;
use Modules\Manager\Entities\Plan;
use Modules\Manager\Entities\SiteOwner;
use Modules\Manager\Entities\Subscription;
use Modules\Manager\Entities\Theme;
use Modules\Manager\Services\FeatureService;
use Modules\Manager\Services\EntitlementWriter;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OwnerController extends Controller
{
    public function __construct(private readonly FeatureService $features, private readonly EntitlementWriter $writer) {}

    public function index(Request $request)
    {
        $query = SiteOwner::query()->with(['domains', 'currentSubscription.plan'])->latest('id');

        if (($term = trim((string) $request->query('q', ''))) !== '') {
            $query->where(function ($w) use ($term) {
                $w->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhereHas('domains', fn ($d) => $d->where('host', 'like', "%{$term}%"));
            });
        }

        return view('manager::owners.index', [
            'title' => 'Sahiblər',
            'owners' => $query->paginate(20)->withQueryString(),
            'filters' => $request->only('q'),
        ]);
    }

    public function create()
    {
        return view('manager::owners.form', $this->formData(null));
    }

    public function store(Request $request)
    {
        $started = microtime(true);
        Log::info('manager.owner.create.start', ['email' => $request->input('email')]);

        $data = $this->validated($request);

        $adminPassword = Str::password(10);

        $owner = $this->timed('record', fn () => SiteOwner::query()->create($data['owner']));
        $this->timed('domains', fn () => $this->syncDomains($owner, $data['domains'] ?? []));
        $this->timed('identity', fn () => $this->ensureTenantIdentity($owner));
        $this->timed('subscription', fn () => $this->syncSubscription($owner, $request));
        $this->timed('overrides', fn () => $this->syncOverrides($owner, $request->input('overrides', [])));

        $owner->load('domains');
        $host = $owner->domains->first()?->host;

        $provision = $this->timed('provision', fn () => $this->provision($owner, $adminPassword), ['host' => $host, 'database' => $owner->db_name]);
        $this->timed('push', fn () => $this->writer->push($owner), ['owner' => $owner->id]);

        Log::info('manager.owner.create.done', [
            'owner' => $owner->id,
            'db' => $owner->db_name,
            'provisioned' => $provision['ok'] ?? false,
            'total_ms' => (int) round((microtime(true) - $started) * 1000),
        ]);

        session()->flash('created_store', [
            'provisioned' => $provision['ok'],
            'command' => $provision['command'] ?? null,
            'exit_code' => $provision['exit_code'] ?? null,
            'output' => $provision['output'] ?? null,
            'database' => $owner->db_name,
            'storage_root' => $owner->storageRoot(),
            'admin_email' => $owner->email,
            'admin_password' => $adminPassword,
            'urls' => $owner->domains->map(fn ($d) => 'https://'.$d->host)->all(),
        ]);

        return redirect()->route('manager.owners.edit', $owner)->with('status', __('Sahib yaradıldı.'));
    }

    public function edit(SiteOwner $owner)
    {
        return view('manager::owners.form', $this->formData($owner->load(['domains', 'currentSubscription', 'ownerFeatures'])));
    }

    public function update(Request $request, SiteOwner $owner)
    {
        $data = $this->validated($request, $owner);

        $owner->update($data['owner']);
        $this->syncDomains($owner, $data['domains'] ?? []);
        $this->ensureTenantIdentity($owner);
        $this->syncSubscription($owner, $request);
        $this->syncOverrides($owner, $request->input('overrides', []));
        $this->writer->push($owner->fresh('domains'));

        return back()->with('status', __('Sahib yeniləndi.'));
    }

    public function updateFeatures(Request $request, SiteOwner $owner)
    {
        $this->syncOverrides($owner, $request->input('overrides', []));
        $this->writer->push($owner->fresh('domains'));

        return back()->with('status', __('Feature-lar yeniləndi.'));
    }



    public function destroy(SiteOwner $owner)
    {
        // Free the domains too, otherwise their hosts stay bound to a
        // soft-deleted owner and can never be reused.
        $owner->domains()->delete();
        $owner->delete();

        return redirect()->route('manager.owners.index')->with('status', __('Sahib silindi.'));
    }

    /**
     * Runs a step and logs its name + duration so the create flow can be
     * watched live (php artisan pail).
     */
    private function timed(string $step, \Closure $callback, array $context = []): mixed
    {
        $start = microtime(true);
        Log::info("manager.owner.{$step}.start", $context);

        try {
            return $callback();
        } finally {
            Log::info("manager.owner.{$step}.done", $context + [
                'ms' => (int) round((microtime(true) - $start) * 1000),
            ]);
        }
    }

    private function provision(SiteOwner $owner, string $adminPassword): array
    {
        $host = $owner->domains->first()?->host;

        if (! $host) {
            return ['ok' => false, 'message' => 'Provision üçün domen yoxdur.'];
        }

        $command = 'php artisan tenant:provision '.$host
            .' --database='.$owner->db_name
            .' --admin-email='.$owner->email
            .' --admin-name='.$owner->name
            .' --admin-phone='.(string) $owner->phone
            .' --admin-password=********';

        try {
            $code = Artisan::call('tenant:provision', [
                'host' => $host,
                '--database' => $owner->db_name,
                '--admin-email' => $owner->email,
                '--admin-password' => $adminPassword,
                '--admin-name' => $owner->name,
                '--admin-phone' => (string) $owner->phone,
            ]);

            return ['ok' => $code === 0, 'command' => $command, 'exit_code' => $code, 'output' => Artisan::output()];
        } catch (\Throwable $e) {
            return ['ok' => false, 'command' => $command, 'exit_code' => 1, 'output' => $e->getMessage()];
        }
    }

    private function formData(?SiteOwner $owner): array
    {
        return [
            'title' => $owner ? 'Sahibi redaktə et' : 'Yeni sahib',
            'owner' => $owner,
            'plans' => Plan::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'themes' => Theme::query()->active()->orderBy('sort_order')->get(),
            'features' => Feature::query()->orderBy('sort_order')->get(),
            'groups' => Feature::query()->orderBy('sort_order')->get()->groupBy('group'),
            'effective' => $owner ? $this->features->resolve($owner) : [],
            'overrides' => $owner ? $owner->ownerFeatures->pluck('value', 'feature_id')->all() : [],
            'usage' => $owner ? [
                'max_products' => $owner->usage_products,
                'max_categories' => $owner->usage_categories,
                'max_staff' => $owner->usage_staff,
                'storage_gb' => (float) $owner->usage_storage_gb,
            ] : [],
            'limits' => $owner ? collect($this->features->resolve($owner))
                ->filter(fn ($f) => ($f['type'] ?? '') === 'limit')
                ->map(fn ($f) => $f['value'])
                ->all() : [],
        ];
    }

    private function validated(Request $request, ?SiteOwner $owner = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('control.site_owners', 'email')->ignore($owner?->id)->whereNull('deleted_at')],
            'phone' => ['nullable', 'string', 'max:40'],
            'theme_id' => ['nullable', 'exists:control.themes,id'],
            'status' => ['required', Rule::in(['active', 'trial', 'suspended', 'cancelled'])],
            'notes' => ['nullable', 'string'],
            'domains' => ['nullable', 'array'],
            'domains.*' => [
                'nullable', 'string', 'max:190',
                function (string $attribute, mixed $value, \Closure $fail) use ($owner) {
                    $host = strtolower(trim((string) $value));

                    if ($host === '') {
                        return;
                    }

                    $taken = Domain::query()
                        ->where('host', $host)
                        ->when($owner?->id, fn ($q) => $q->where('site_owner_id', '!=', $owner->id))
                        ->exists();

                    if ($taken) {
                        $fail("Bu domen başqa sahibə bağlıdır: {$host}");
                    }
                },
            ],
            'plan_id' => ['nullable', 'exists:control.plans,id'],
            'sub_status' => ['nullable', Rule::in(['trialing', 'active', 'past_due', 'cancelled', 'expired'])],
            'sub_price' => ['nullable', 'numeric', 'min:0'],
            'sub_starts' => ['nullable', 'date'],
            'sub_ends' => ['nullable', 'date'],
        ];

        $v = $request->validate($rules);

        return [
            'owner' => Arr::only($v, ['name', 'company', 'email', 'phone', 'status', 'notes', 'theme_id']),
            'domains' => $request->input('domains', []),
        ];
    }

    private function ensureTenantIdentity(SiteOwner $owner): void
    {
        $owner->loadMissing('domains');

        if (empty($owner->tenant_slug)) {
            $owner->tenant_slug = $this->uniqueTenantSlug($owner, $owner->suggestedTenantSlug());
        }

        if (empty($owner->db_name)) {
            $owner->db_name = $owner->suggestedDbName();
        }

        if ($owner->isDirty(['tenant_slug', 'db_name'])) {
            $owner->save();
        }
    }

    private function uniqueTenantSlug(SiteOwner $owner, string $slug): string
    {
        $base = $slug !== '' ? $slug : 'owner'.$owner->id;
        $candidate = $base;
        $counter = 2;

        while (SiteOwner::query()
            ->where('tenant_slug', $candidate)
            ->when($owner->exists, fn ($q) => $q->whereKeyNot($owner->id))
            ->exists()) {
            $suffix = '-'.$counter++;
            $candidate = substr($base, 0, 50 - strlen($suffix)).$suffix;
        }

        return $candidate;
    }

    private function syncDomains(SiteOwner $owner, array $hosts): void
    {
        $hosts = collect($hosts)
            ->map(fn ($h) => strtolower(trim((string) $h)))
            ->filter()
            ->unique()
            ->values();

        if ($hosts->isEmpty()) {
            $hosts = collect([$this->uniqueGeneratedHost($owner)]);
        }

        $removed = $owner->domains()->whereNotIn('host', $hosts->all() ?: [''])->pluck('host')->all();

        $owner->domains()->whereNotIn('host', $hosts->all() ?: [''])->delete();

        foreach ($hosts as $i => $host) {
            $owner->domains()->updateOrCreate(
                ['host' => $host],
                ['is_primary' => $i === 0]
            );
        }
    }


    private function uniqueGeneratedHost(SiteOwner $owner): string
    {
        $baseDomain = trim((string) config('manager.base_domain'), '.');
        $source = $owner->company ?: $owner->name ?: 'store-'.$owner->id;
        $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($source)), '-');
        $slug = $slug !== '' ? $slug : 'store-'.$owner->id;
        $baseSlug = substr($slug, 0, 50);
        $candidate = "{$baseSlug}.{$baseDomain}";
        $counter = 2;

        while (Domain::query()
            ->where('host', $candidate)
            ->where('site_owner_id', '!=', $owner->id)
            ->exists()) {
            $suffix = '-'.$counter++;
            $candidateSlug = substr($baseSlug, 0, 50 - strlen($suffix)).$suffix;
            $candidate = "{$candidateSlug}.{$baseDomain}";
        }

        return $candidate;
    }

    private function syncSubscription(SiteOwner $owner, Request $request): void
    {
        $planId = $request->input('plan_id');

        if (! $planId) {
            return;
        }

        $plan = Plan::query()->find($planId);
        $subscription = $owner->subscriptions()->latest('id')->first()
            ?? new Subscription(['site_owner_id' => $owner->id]);

        $subscription->fill([
            'plan_id' => $plan->id,
            'status' => $request->input('sub_status') ?: 'active',
            'price' => $request->input('sub_price') !== null && $request->input('sub_price') !== '' ? $request->input('sub_price') : $plan->price,
            'starts_at' => $request->input('sub_starts') ?: now(),
            'ends_at' => $request->input('sub_ends') ?: null,
        ])->save();
    }

    private function syncOverrides(SiteOwner $owner, array $overrides): void
    {
        foreach ($overrides as $featureId => $value) {
            $value = is_string($value) ? trim($value) : $value;

            if ($value === null || $value === '') {
                OwnerFeature::query()->where('site_owner_id', $owner->id)->where('feature_id', $featureId)->delete();

                continue;
            }

            OwnerFeature::query()->updateOrCreate(
                ['site_owner_id' => $owner->id, 'feature_id' => $featureId],
                ['value' => (string) $value]
            );
        }
    }
}
