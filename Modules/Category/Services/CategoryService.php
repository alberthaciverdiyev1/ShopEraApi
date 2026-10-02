<?php

namespace Modules\Category\Services;

use App\Helpers\TranslateHelper as Translate;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Category\Entities\Category;
use Modules\Category\Http\Transformers\CategoryResource;
use Modules\Product\Entities\Product;

class CategoryService
{
    private Category $model;

    public function __construct(Category $model)
    {
        $this->model = $model;
    }

    public function list($request): JsonResponse
    {
        $params = $request->all();
        $query = $this->model->query();

        if (! $request->has('all')) {
            $query->where('parent_id', null);
        }

        $query = filterLike($query, ['name', 'description'], $params);
        $data = $query->withCount('products')->orderByDesc('sort_order')->get();
        $this->attachVisibleProductCounts($data);

        return responseHelper(__('Categories retrieved successfully.'), 200, CategoryResource::collection($data));

    }

    public function listAdmin($request): JsonResponse
    {
        $params = $request->all();

        $query = $this->model->query();

        if (! $request->has('all')) {
            $query->whereNull('parent_id');
        }

        $query = filterLike($query, ['name', 'description'], $params);

        $categories = $query->orderByDesc('sort_order')->get();

        $data = $categories->map(function ($category) {
            $name = $category->getRawOriginal('name');

            if (is_string($name)) {
                $decoded = json_decode($name, true);
                $name = json_last_error() === JSON_ERROR_NONE ? $decoded : $name;
            }

            return [
                'id' => $category->id,
                'name' => $name,
                'image' => $category->image,
                'description' => $category->description,
                'parent_id' => $category->parent_id,
                'is_active' => $category->is_active,
                'sort_order' => $category->sort_order,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Categories retrieved successfully.',
            'data' => $data,
        ], 200);
    }

    public function listWithProducts($request): JsonResponse
    {
        $params = $request->all();

        $query = $this->model
            ->with([
                'children',
                'products' => function ($q) {
                    $q->with(['colors', 'sizes', 'images', 'category', 'brand'])
                        ->publiclyAvailable()
                        ->withAvg('reviews', 'rate')
                        ->withCount('reviews')
                        ->orderByDesc('sales_count')
                        ->limit(10);
                },
            ])
            ->withCount('children')
            ->orderByDesc('children_count');

        if (! $request->has('all')) {
            $query->whereNull('parent_id');
        }

        $query = filterLike($query, ['name', 'description'], $params);
        $categories = $query->get();

        $categories->each(function ($category) {
            $allChildIds = $this->getAllChildCategoryIds($category->id);

            if (! empty($allChildIds)) {
                $childProducts = Product::whereIn('category_id', $allChildIds)
                    ->publiclyAvailable()
                    ->with(['colors', 'sizes', 'images', 'category', 'brand'])
                    ->withAvg('reviews', 'rate')
                    ->withCount('reviews')
                    ->orderByDesc('sales_count')
                    ->limit(10)
                    ->get();

                $merged = $category->products->merge($childProducts)->unique('id');
                $category->setRelation('products', $merged);
            }

            $category->products->transform(function ($product) {
                $product->rate = $product->reviews_avg_rate ? round($product->reviews_avg_rate, 2) : 0;
                $product->rate_count = $product->reviews_count;
                $product->is_favorite = Auth::check()
                    ? $product->favoritedBy()->where('user_id', Auth::id())->exists()
                    : false;

                return $product;
            });
        });

        return responseHelper(__('Categories with products retrieved successfully.'),
            200,
            CategoryResource::collection($categories)
        );
    }

    private function getAllChildCategoryIds($parentId)
    {
        $childIds = Category::where('parent_id', $parentId)->pluck('id');
        $all = collect($childIds);

        if ($childIds->isNotEmpty()) {
            foreach ($childIds as $childId) {
                $all = $all->merge($this->getAllChildCategoryIds($childId));
            }
        }

        return $all->unique()->values()->toArray();
    }

    private function attachVisibleProductCounts(Collection $categories): void
    {
        if ($categories->isEmpty()) {
            return;
        }

        $allCategories = $this->model->query()->select(['id', 'parent_id'])->get();
        $childrenByParent = $allCategories->groupBy(fn (Category $category) => $category->parent_id ?? 0);
        $directCounts = Product::query()
            ->publiclyAvailable()
            ->selectRaw('category_id, COUNT(*) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        $descendantIds = function (int $categoryId) use (&$descendantIds, $childrenByParent): array {
            $children = $childrenByParent->get($categoryId, collect());
            $ids = $children->pluck('id')->all();

            foreach ($children as $child) {
                $ids = array_merge($ids, $descendantIds((int) $child->id));
            }

            return $ids;
        };

        $categories->each(function (Category $category) use ($descendantIds, $directCounts) {
            $ids = array_merge([(int) $category->id], $descendantIds((int) $category->id));
            $total = collect($ids)->sum(fn (int $id) => (int) ($directCounts[$id] ?? 0));
            $category->setAttribute('products_count', $total);
        });
    }

    public function details(int $id): JsonResponse
    {
        try {
            $category = $this->model->with('children')->findOrFail($id);

            return responseHelper(__('Category details retrieved successfully.'), 200, CategoryResource::make($category));

        } catch (ModelNotFoundException $e) {
            return responseHelper(__('Category not found.'), 403, []);
        }
    }

    public function add($request): JsonResponse
    {
        $validated = $request->validated();
        $languages = ['az', 'ru', 'en', 'tr'];

        $name = $validated['name'] ?? ['az' => ''];
        foreach ($languages as $lang) {
            if (empty($name[$lang])) {
                $name[$lang] = Translate::translate($name['az'], $lang);
            }
            $name[$lang] = Str::title($name[$lang]);
        }

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time().'_'.$image->getClientOriginalName();

            $directory = TenantContext::storagePath('categories');

            if (! Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory, 0755, true);
            }

            $image->storeAs($directory, $imageName, 'public');

            $validated['image'] = "{$directory}/{$imageName}";
        }

        unset($validated['name']);

        return handleTransaction(function () use ($validated, $name) {
            $category = $this->model->create($validated);
            $category->update(['name' => $name]);

            return $category->refresh();
        }, 'Category added successfully.', CategoryResource::class);
    }

    //    public function update($request, int $id): JsonResponse
    //    {
    //        $validated = $request->validated();
    //
    //        if ($request->hasFile('image')) {
    //            $image = $request->file('image');
    //            $imageName = time() . '_' . $image->getClientOriginalName();
    //
    //            if (!Storage::disk('public')->exists('categories')) {
    //                Storage::disk('public')->makeDirectory('categories', 0755, true);
    //            }
    //            $image->storeAs('categories', $imageName, 'public');
    //            $validated['image'] = 'categories/' . $imageName;
    //        }
    //
    //        return handleTransaction(
    //            function () use ($validated, $id) {
    //                $category = $this->model->findOrFail($id);
    //                $category->update($validated);
    //                return $category->refresh();
    //            },
    //            'Category updated successfully.',
    //            CategoryResource::class
    //        );
    //    }

    public function update($request, int $id): JsonResponse
    {
        $validated = $request->validated();
        $languages = ['az', 'ru', 'en', 'tr'];

        if (! empty($validated['name'])) {
            $name = is_string($validated['name']) ? ['az' => $validated['name']] : $validated['name'];

            foreach ($languages as $lang) {
                $name[$lang] = Str::title($name[$lang] ?? Translate::translate($name['az'], $lang));
            }

            unset($validated['name']);
        }

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time().'_'.$image->getClientOriginalName();

            $directory = TenantContext::storagePath('categories');
            Storage::disk('public')->makeDirectory($directory);
            $image->storeAs($directory, $imageName, 'public');

            $validated['image'] = "{$directory}/{$imageName}";
        }

        $newPos = $validated['sort_order'] ?? null;

        return handleTransaction(function () use ($validated, $id, $name, $newPos) {

            $category = $this->model->findOrFail($id);
            $oldPos = $category->sort_order;

            $this->model->whereNull('parent_id')
                ->orderBy('sort_order')
                ->get()
                ->each(fn ($c, $i) => $c->update(['sort_order' => $i + 1]));

            if ($category->parent_id !== null) {
                unset($validated['sort_order']);
                $category->update($validated ?? []);
                if (isset($name)) {
                    $category->update(['name' => $name]);
                }

                return $category->refresh();
            }

            $category->refresh();
            $oldPos = $category->sort_order;

            if ($newPos && $newPos != $oldPos) {

                if ($newPos < $oldPos) {
                    $this->model->whereNull('parent_id')
                        ->whereBetween('sort_order', [$newPos, $oldPos - 1])
                        ->increment('sort_order');
                } else {
                    $this->model->whereNull('parent_id')
                        ->whereBetween('sort_order', [$oldPos + 1, $newPos])
                        ->decrement('sort_order');
                }

                $validated['sort_order'] = $newPos;
            } else {
                unset($validated['sort_order']);
            }

            $category->update($validated ?? []);
            if (isset($name)) {
                $category->update(['name' => $name]);
            }

            return $category->refresh();

        }, 'Category updated successfully', CategoryResource::class);

    }

    public function delete(int $id): JsonResponse
    {
        return handleTransaction(
            function () use ($id) {
                $category = $this->model->findOrFail($id);
                Product::where('category_id', $category->id)->update(['category_id' => null]);
                $category->delete();

                return $category;
            },
            'Category deleted successfully.'
        );
    }

    /** Admin listing query: tree order (children after parent) with optional q=. */
    public function adminQuery(\Illuminate\Http\Request $request, array $locales): \Illuminate\Database\Eloquent\Builder
    {
        $query = $this->model->newQuery();
        $term = trim((string) $request->query('q', ''));

        if ($term !== '') {
            $escaped = addcslashes($term, '%_\\');

            return $query->where(function ($inner) use ($escaped, $locales) {
                foreach ($locales as $locale) {
                    $inner->orWhere("name->{$locale}", 'like', "%{$escaped}%");
                }
            })->orderBy('id');
        }

        return $query->orderByRaw('COALESCE(parent_id, id)')->orderBy('id');
    }

    /** Flatten rows into depth-annotated nodes so children follow parents. */
    public function treeNodes($rows): array
    {
        $items = $rows instanceof \Illuminate\Pagination\AbstractPaginator ? $rows->getCollection() : collect($rows);
        $byParent = $items->groupBy(fn (Category $c) => $c->parent_id ?? 0);
        $withChildren = $items->pluck('parent_id')->filter()->unique()->flip();

        $nodes = [];
        $walk = function (int $parentId, int $depth, string $chain) use (&$walk, &$nodes, $byParent, $withChildren) {
            foreach ($byParent->get($parentId, collect()) as $category) {
                $nodes[] = [
                    'category' => $category,
                    'depth' => $depth,
                    'chain' => trim($chain),
                    'hasChildren' => $withChildren->has($category->id) || $byParent->get($category->id, collect())->isNotEmpty(),
                ];
                $walk((int) $category->id, $depth + 1, $chain.' '.$category->id);
            }
        };
        $walk(0, 0, '');

        return $nodes;
    }

    /** Parent select options: [id => label] with a blank entry. */
    public function parentOptions(): array
    {
        $options = ['' => '— Ana kateqoriya —'];

        foreach ($this->model->newQuery()->orderBy('id')->get() as $category) {
            $options[$category->id] = admin_label($category, 'name', '#'.$category->id);
        }

        return $options;
    }

    /** [parent, children] for the product form subcategory snippet. */
    public function childrenFor(?int $parentId): array
    {
        $parent = $parentId ? $this->model->newQuery()->find($parentId) : null;
        $children = $parentId
            ? $this->model->newQuery()->where('parent_id', $parentId)->orderBy('id')->get()
            : collect();

        return [$parent, $children];
    }
}
