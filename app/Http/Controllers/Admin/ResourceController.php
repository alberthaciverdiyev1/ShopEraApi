<?php

namespace App\Http\Controllers\Admin;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;

/**
 * Declarative CRUD used by the simple catalogue/content screens. A concrete
 * controller only describes its model, table columns and form fields; the
 * listing, search, pagination and htmx create/edit modal come for free.
 */
abstract class ResourceController extends AdminController
{
    /** Eloquent model class. */
    protected string $model;

    /** Route name prefix, e.g. "admin.brands". */
    protected string $route;

    /** @var array<int,array{key:string,label:string,type?:string}> */
    protected array $columns = [];

    /** @var array<int,array<string,mixed>> */
    protected array $fields = [];

    /** Columns searched with a LIKE when ?q= is present (JSON paths allowed). */
    protected array $searchable = [];

    /** Relations eager loaded for the listing. */
    protected array $with = [];

    protected string $orderColumn = 'id';

    protected string $orderDirection = 'desc';

    protected int $perPage = 20;

    /** Lazily resolved enabled locales (feature flags lang_xx). */
    private ?array $localesCache = null;

    protected function locales(): array
    {
        return $this->localesCache ??= $this->enabledLocales();
    }

    /** Storage folder used for file/image fields without an explicit path. */
    protected string $storagePath = '';

    /** Optional "is_active" style toggle filter on the listing. */
    protected ?string $activeColumn = null;

    public function index(Request $request)
    {
        $rows = $this->listingQuery($request)->paginate($this->perPage)->withQueryString();

        if ($this->isHtmx($request)) {
            return view($this->tableView(), $this->tableData($rows));
        }

        return view('admin.resources.index', array_merge($this->tableData($rows), [
            'title' => $this->title,
            'fields' => $this->visibleFields(),
            'searchable' => $this->searchable !== [],
            'activeColumn' => $this->activeColumn,
            'filters' => $request->only(['q', 'is_active']),
        ]));
    }

    public function create(Request $request)
    {
        return view($this->formView(), [
            'item' => null,
            'fields' => $this->visibleFields(),
            'route' => $this->route,
            'title' => $this->title,
            'locales' => $this->locales(),
            'options' => $this->optionsMap(),
        ]);
    }

    public function edit(Request $request, int|string $id)
    {
        $item = $this->model::query()->with($this->with)->findOrFail($id);

        return view($this->formView(), [
            'item' => $item,
            'fields' => $this->visibleFields(),
            'route' => $this->route,
            'title' => $this->title,
            'locales' => $this->locales(),
            'options' => $this->optionsMap(),
        ]);
    }

    protected array $pendingSync = [];

    public function store(Request $request)
    {
        $this->pendingSync = [];

        $validator = Validator::make($request->all(), $this->buildRules());

        if ($validator->fails()) {
            return $this->formErrorResponse($request, $validator->errors()->toArray(), null);
        }

        $data = $this->prepareData($validator->validated(), $request, null);
        $item = $this->model::create($data);
        $this->syncRelations($item);
        $this->afterSave($item, $request, true);

        return $this->tableResponse($request, __('Yadda saxlanıldı.'));
    }

    public function update(Request $request, int|string $id)
    {
        $this->pendingSync = [];

        $item = $this->model::query()->findOrFail($id);

        $validator = Validator::make($request->all(), $this->buildRules($item));

        if ($validator->fails()) {
            return $this->formErrorResponse($request, $validator->errors()->toArray(), $item);
        }

        $data = $this->prepareData($validator->validated(), $request, $item);
        $item->update($data);
        $this->syncRelations($item);
        $this->afterSave($item, $request, false);

        return $this->tableResponse($request, __('Yeniləndi.'));
    }

    public function destroy(Request $request, int|string $id)
    {
        $item = $this->model::query()->findOrFail($id);
        $this->beforeDelete($item);
        $item->delete();
        $this->afterDelete($item);

        return $this->tableResponse($request, __('Silindi.'));
    }

    // ─── Hooks ───

    protected function listingQuery(Request $request): Builder
    {
        $query = $this->model::query()->with($this->with);

        if ($this->searchable !== [] && ($term = trim((string) $request->query('q', ''))) !== '') {
            $term = addcslashes($term, '%_\\');
            $query->where(function (Builder $inner) use ($term) {
                foreach ($this->searchable as $i => $column) {
                    $i === 0
                        ? $inner->where($column, 'like', "%{$term}%")
                        : $inner->orWhere($column, 'like', "%{$term}%");
                }
            });
        }

        if ($this->activeColumn && $request->filled('is_active')) {
            $query->where($this->activeColumn, (bool) $request->boolean('is_active'));
        }

        return $query->orderBy($this->orderColumn, $this->orderDirection);
    }

    protected function buildRules(?Model $item = null): array
    {
        $rules = [];

        foreach ($this->visibleFields() as $field) {
            $name = $field['name'];
            $type = $field['type'] ?? 'text';
            $base = $field['rules'] ?? [];

            if ($type === 'multiselect') {
                if (! empty($field['sync'])) {
                    $this->pendingSync[$field['sync']] = array_map('intval', (array) ($data[$name] ?? []));
                    unset($data[$name]);
                }

                continue;
            }

            if ($type === 'lines') {
                $lines = array_values(array_filter(array_map('trim', explode("\n", (string) ($data[$name] ?? ''))), fn ($v) => $v !== ''));
                $data[$name] = $lines;

                continue;
            }

            if (in_array($type, ['file', 'image', 'video'], true)) {
                // A file is only mandatory when creating; editing without
                // re-uploading must keep the existing file.
                if ($item) {
                    $base = array_values(array_filter($base, fn ($rule) => ! str_starts_with($rule, 'required')));
                }
                $rules[$name] = array_merge(['nullable'], $base);

                continue;
            }

            if (str_starts_with($type, 'translatable_')) {
                foreach ($this->locales() as $locale) {
                    $localeRules = $locale === 'az'
                        ? $base
                        : array_values(array_diff($base, ['required']));
                    $rules["{$name}.{$locale}"] = $localeRules ?: ['nullable'];
                }

                continue;
            }

            if ($type === 'checkbox') {
                $rules[$name] = ['nullable'];

                continue;
            }

            if ($type === 'multiselect') {
                $rules[$name] = array_merge(['nullable', 'array'], $base);
                $rules["{$name}.*"] = ['exists:'.$this->relationTable($field), 'id'];

                continue;
            }

            if ($type === 'lines') {
                $rules[$name] = ['nullable', 'string'];

                continue;
            }

            $rules[$name] = $base ?: ['nullable'];
        }

        return $rules;
    }

    protected function prepareData(array $data, Request $request, ?Model $item): array
    {
        foreach ($this->visibleFields() as $field) {
            $name = $field['name'];
            $type = $field['type'] ?? 'text';

            if ($type === 'checkbox') {
                $data[$name] = $request->boolean($name);

                continue;
            }

            if ($type === 'password') {
                if (! empty($data[$name])) {
                    $data[$name] = Hash::make($data[$name]);
                } else {
                    unset($data[$name]);
                }

                continue;
            }

            if (str_starts_with($type, 'translatable_')) {
                $values = $this->fillLocales((array) ($data[$name] ?? []));
                $data[$name] = $values;

                continue;
            }

            if ($type === 'multiselect') {
                if (! empty($field['sync'])) {
                    $this->pendingSync[$field['sync']] = array_map('intval', (array) ($data[$name] ?? []));
                    unset($data[$name]);
                }

                continue;
            }

            if ($type === 'lines') {
                $lines = array_values(array_filter(array_map('trim', explode("\n", (string) ($data[$name] ?? ''))), fn ($v) => $v !== ''));
                $data[$name] = $lines;

                continue;
            }

            if (in_array($type, ['file', 'image', 'video'], true)) {
                unset($data[$name]);

                if ($request->hasFile($name)) {
                    $data[$name] = $request->file($name)->store(
                        TenantContext::storagePath($field['path'] ?? $this->defaultStoragePath()),
                        'public'
                    );
                } elseif ($request->boolean('remove_'.$name)) {
                    $data[$name] = null;
                }
            }
        }

        return $data;
    }

    /** Mirror az into the remaining locales, so spatie always stores all four. */
    protected function fillLocales(array $values): array
    {
        $source = $values['az'] ?? reset($values) ?: '';

        foreach ($this->locales() as $locale) {
            if (empty($values[$locale])) {
                $values[$locale] = $source;
            }
        }

        return $values;
    }

    protected function syncRelations(Model $item): void
    {
        foreach ($this->pendingSync as $relation => $ids) {
            $item->{$relation}()->sync($ids);
        }

        $this->pendingSync = [];
    }

    protected function afterSave(Model $item, Request $request, bool $created): void {}

    protected function beforeDelete(Model $item): void {}

    protected function afterDelete(Model $item): void {}

    // ─── Response helpers ───

    protected function tableResponse(Request $request, string $message)
    {
        $rows = $this->listingQuery($request)->paginate($this->perPage)->withQueryString();

        return response()
            ->view($this->tableView(), $this->tableData($rows), 200)
            ->header('HX-Trigger', $this->htmxTriggers([
                'toast' => ['type' => 'success', 'message' => $message],
                'modal:close' => true,
            ]));
    }

    protected function formErrorResponse(Request $request, array $errors, ?Model $item)
    {
        // htmx only swaps 2xx responses, so answer with 200 and re-target the
        // modal; the validation messages live inside the returned form.
        return response()
            ->view($this->formView(), [
                'item' => $item,
                'fields' => $this->visibleFields(),
                'route' => $this->route,
                'title' => $this->title,
                'locales' => $this->locales(),
                'options' => $this->optionsMap(),
                'errors' => (new ViewErrorBag)->put(
                    'default',
                    new MessageBag($errors)
                ),
            ], 200)
            ->header('HX-Retarget', '#modal-root');
    }

    protected function tableData($rows): array
    {
        return [
            'rows' => $rows,
            'columns' => $this->columns,
            'route' => $this->route,
            'perPage' => $this->perPage,
            'columnMaps' => $this->columnMaps(),
        ];
    }

    protected function tableView(): string
    {
        return 'admin.resources._table';
    }

    protected function formView(): string
    {
        return 'admin.resources._form';
    }

    /**
     * Fields the controller decides to show. There is no feature/plan gating
     * any more, so every declared field is visible.
     */
    protected function visibleFields(): array
    {
        return array_values(array_filter($this->fields, function (array $field): bool {

            return true;
        }));
    }

    /** @return array<string,array<int|string,mixed>> Options for select fields. */
    protected function optionsMap(): array
    {
        $map = [];

        foreach ($this->visibleFields() as $field) {
            if (in_array($field['type'] ?? 'text', ['select', 'multiselect'], true)) {
                $map[$field['name']] = $this->resolveOptions($field);
            }
        }

        return $map;
    }

    /** @return array<string,array<int|string,string>> Value labels for "map" columns. */
    protected function columnMaps(): array
    {
        return [];
    }

    protected function resolveOptions(array $field): array
    {
        $options = $field['options'] ?? [];

        return is_callable($options) ? $options() : $options;
    }

    protected function defaultStoragePath(): string
    {
        return $this->storagePath !== '' ? $this->storagePath : Str::afterLast($this->route, '.');
    }

    protected function relationTable(array $field): string
    {
        if (! empty($field['relation'])) {
            return $field['relation'];
        }

        return Str::plural(Str::snake(Str::afterLast($field['name'], '_id')));
    }
}
