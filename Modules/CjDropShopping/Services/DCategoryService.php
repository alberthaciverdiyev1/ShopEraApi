<?php

namespace Modules\CjDropShopping\Services;

use App\Helpers\TranslateHelper as Translate;
use Illuminate\Support\Str;
use Modules\Category\Entities\Category;
use Modules\Category\Services\CategoryService;

/**
 * CJ Dropshipping categories.
 *
 * Fetches the CJ category tree and imports it into the local catalogue. The
 * actual creation always goes through the normal {@see CategoryService}, so CJ
 * categories behave exactly like categories created from the admin/API.
 */
class DCategoryService extends DBaseService
{
    /** @var array<string,string> per-run translation memo, keyed by "lang|text" */
    private array $translations = [];

    public function __construct(
        DAuthService $auth,
        private readonly CategoryService $categoryService,
    ) {
        parent::__construct($auth);
    }

    /** Raw CJ category tree (`product/getCategory`). */
    public function categories(): array
    {
        return $this->get('product/getCategory');
    }

    /**
     * Fetch the CJ categories and upsert them locally (parents first) using the
     * normal CategoryService. Idempotent: matched by `cj_category_id`.
     *
     * @param  bool  $translate  Translate the English names into az/ru/tr.
     * @return array{fetched:int,created:int,updated:int,map:array<string,int>}
     */
    public function sync(bool $translate = true): array
    {
        $items = $this->flatten($this->categories());
        $byId = collect($items)->keyBy('id');
        $map = [];
        $created = 0;
        $updated = 0;

        $pending = $items;

        while ($pending !== []) {
            $progress = false;

            foreach ($pending as $key => $item) {
                $parentCjId = $item['parent_id'];
                $parentKnown = $parentCjId === null
                    || ! isset($byId[$parentCjId])
                    || array_key_exists($parentCjId, $map);

                if (! $parentKnown) {
                    continue;
                }

                [$category, $wasCreated] = $this->store(
                    $item,
                    $parentCjId !== null ? ($map[$parentCjId] ?? null) : null,
                    $translate,
                );

                $map[$item['id']] = $category->id;
                $wasCreated ? $created++ : $updated++;
                unset($pending[$key]);
                $progress = true;
            }

            if (! $progress) {
                // Broken parent references / cycles: import the rest as roots.
                foreach ($pending as $key => $item) {
                    [$category, $wasCreated] = $this->store($item, null, $translate);
                    $map[$item['id']] = $category->id;
                    $wasCreated ? $created++ : $updated++;
                    unset($pending[$key]);
                }
            }
        }

        return [
            'fetched' => count($items),
            'created' => $created,
            'updated' => $updated,
            'map' => $map,
        ];
    }

    /**
     * @param  array{id:string,name:string,parent_id:?string,image:?string,sort_order:int}  $item
     * @return array{0:Category,1:bool}
     */
    private function store(array $item, ?int $parentId, bool $translate): array
    {
        $name = $this->localizedName($item['name'], $translate);

        $existing = Category::withTrashed()->where('cj_category_id', $item['id'])->first();

        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
            }

            $existing->update([
                'name' => $name,
                'parent_id' => $parentId,
                'is_active' => true,
            ]);

            if (empty($existing->image) && ! empty($item['image'])) {
                $existing->update(['image' => $item['image']]);
            }

            return [$existing, false];
        }

        $attributes = [
            'cj_category_id' => $item['id'],
            'name' => $name,
            'parent_id' => $parentId,
            'is_active' => true,
            'sort_order' => $item['sort_order'],
        ];

        if (! empty($item['image'])) {
            $attributes['image'] = $item['image'];
        }

        return [$this->categoryService->createFromData($attributes), true];
    }

    /**
     * Build the az/en/ru/tr name map. English is the CJ source; az/ru/tr are
     * machine translated when enabled.
     *
     * @return array<string,string>
     */
    private function localizedName(string $english, bool $translate): array
    {
        $english = trim($english);
        $name = ['en' => Str::title($english)];

        foreach (['az', 'ru', 'tr'] as $locale) {
            $name[$locale] = ! $translate || $english === ''
                ? Str::title($english)
                : Str::title($this->translation($english, $locale));
        }

        return $name;
    }

    private function translation(string $text, string $locale): string
    {
        $key = $locale.'|'.$text;

        return $this->translations[$key] ??= Translate::translate($text, $locale, 'en');
    }

    /**
     * Flatten a (possibly nested) CJ category response into a plain list.
     *
     * @return array<int,array{id:string,name:string,parent_id:?string,image:?string,sort_order:int}>
     */
    private function flatten(array $items, ?string $parentId = null): array
    {
        $flat = [];

        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $id = (string) ($item['categoryId'] ?? $item['id'] ?? '');

            if ($id === '') {
                continue;
            }

            $parent = (string) ($item['categoryParentId'] ?? $item['parentId'] ?? $parentId ?? '');

            $flat[] = [
                'id' => $id,
                'name' => (string) ($item['categoryName'] ?? $item['name'] ?? ''),
                'parent_id' => $parent !== '' ? $parent : null,
                'image' => $item['categoryImage'] ?? $item['image'] ?? null,
                'sort_order' => (int) ($item['sortOrder'] ?? $item['sort_order'] ?? $index),
            ];

            $children = $item['children'] ?? $item['childList'] ?? null;

            if (is_array($children) && $children !== []) {
                $flat = array_merge($flat, $this->flatten($children, $id));
            }
        }

        return $flat;
    }
}
