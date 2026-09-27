<?php

namespace Modules\Category\Services;

use App\Helpers\TranslateHelper as Translate;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Category\Http\Entities\Category;
use Modules\Category\Http\Transformers\CategoryResource;
use Modules\Product\Http\Entities\Product;

class CategoryService
{
    private Category $model;

    /**
     * @param Category $model
     */
    function __construct(Category $model)
    {
        $this->model = $model;
    }

    /**
     * @param $request
     * @return JsonResponse
     */
    public function list($request): JsonResponse
    {
        $params = $request->all();
        $query = $this->model->query();

        if (!$request->has('all')) {
            $query->where('parent_id', null);
        }

        $query = filterLike($query, ['name', 'description'], $params);
        $data = $query->orderByDesc('sort_order')->get();

        return responseHelper(__('Categories retrieved successfully.'), 200, CategoryResource::collection($data));

    }

    public function listAdmin($request): JsonResponse
    {
        $params = $request->all();

        $query = $this->model->query();

        if (!$request->has('all')) {
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
                }
            ])
            ->withCount('children')
            ->orderByDesc('children_count');

        if (!$request->has('all')) {
            $query->whereNull('parent_id');
        }

        $query = filterLike($query, ['name', 'description'], $params);
        $categories = $query->get();

        $categories->each(function ($category) {
            $allChildIds = $this->getAllChildCategoryIds($category->id);

            if (!empty($allChildIds)) {
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

    /**
     * @param int $id
     * @return JsonResponse
     */
    public function details(int $id): JsonResponse
    {
        try {
            $category = $this->model->with('children')->findOrFail($id);

            return responseHelper(__('Category details retrieved successfully.'), 200, CategoryResource::make($category));

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return responseHelper(__('Category not found.'), 403, []);
        }
    }

    /**
     * @param $request
     * @return JsonResponse
     */

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
            $imageName = time() . '_' . $image->getClientOriginalName();

            if (!Storage::disk('public')->exists('categories')) {
                Storage::disk('public')->makeDirectory('categories', 0755, true);
            }

            $image->storeAs('categories', $imageName, 'public');

            $validated['image'] = 'categories/' . $imageName;
        }

        unset($validated['name']);

        return handleTransaction(function () use ($validated, $name) {
            $category = $this->model->create($validated);
            $category->update(['name' => $name]);

            return $category->refresh();
        }, 'Category added successfully.', CategoryResource::class);
    }


    /**
     * @param $request
     * @param int $id
     * @return JsonResponse
     */
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

    /**
     * @param $request
     * @param int $id
     * @return JsonResponse
     */

    public function update($request, int $id): JsonResponse
    {
        $validated = $request->validated();
        $languages = ['az', 'ru', 'en', 'tr'];

        if (!empty($validated['name'])) {
            $name = is_string($validated['name']) ? ['az' => $validated['name']] : $validated['name'];

            foreach ($languages as $lang) {
                $name[$lang] = Str::title($name[$lang] ?? Translate::translate($name['az'], $lang));
            }

            unset($validated['name']);
        }

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time().'_'.$image->getClientOriginalName();

            Storage::disk('public')->makeDirectory('categories');
            $image->storeAs('categories',$imageName,'public');

            $validated['image'] = 'categories/'.$imageName;
        }

        $newPos = $validated['sort_order'] ?? null;

        return handleTransaction(function () use ($validated, $id, $name, $newPos) {

            $category = $this->model->findOrFail($id);
            $oldPos = $category->sort_order;

            $this->model->whereNull('parent_id')
                ->orderBy('sort_order')
                ->get()
                ->each(fn($c,$i)=>$c->update(['sort_order'=>$i+1]));

            if ($category->parent_id !== null) {
                unset($validated['sort_order']);
                $category->update($validated ?? []);
                if(isset($name)) $category->update(['name'=>$name]);
                return $category->refresh();
            }

            $category->refresh();
            $oldPos = $category->sort_order;

            if($newPos && $newPos != $oldPos){

                if($newPos < $oldPos){
                    $this->model->whereNull('parent_id')
                        ->whereBetween('sort_order',[$newPos,$oldPos-1])
                        ->increment('sort_order');
                }else{
                    $this->model->whereNull('parent_id')
                        ->whereBetween('sort_order',[$oldPos+1,$newPos])
                        ->decrement('sort_order');
                }

                $validated['sort_order']=$newPos;
            }else{
                unset($validated['sort_order']);
            }

            $category->update($validated ?? []);
            if(isset($name)) $category->update(['name'=>$name]);

            return $category->refresh();

        }, 'Category updated successfully', CategoryResource::class);

    }

    /**
     * @param int $id
     * @return JsonResponse
     */
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

}
