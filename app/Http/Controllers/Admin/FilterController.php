<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Modules\Category\Entities\Category;
use Modules\Filter\Entities\ProductFilter;
use Modules\Filter\Entities\Filter;

class FilterController extends ResourceController
{
    protected string $title = 'Filtrlər';

    protected string $model = Filter::class;

    protected string $route = 'admin.filters';

    protected array $searchable = ['title->az', 'title->en'];

    protected array $columns = [
        ['key' => 'title', 'label' => 'Ad', 'type' => 'translatable'],
        ['key' => 'type', 'label' => 'Tip', 'type' => 'text'],
        ['key' => 'options', 'label' => 'Seçimlər', 'type' => 'list'],
    ];

    protected array $fields = [
        ['name' => 'title', 'label' => 'Ad', 'type' => 'translatable_text', 'rules' => ['required', 'max:255'], 'col' => 8],
        ['name' => 'type', 'label' => 'Tip', 'type' => 'select', 'col' => 4, 'options' => [
            'select' => 'Tək seçim',
            'multiselect' => 'Çox seçim',
            'color' => 'Rəng',
            'range' => 'Aralıq',
        ]],
        ['name' => 'options', 'label' => 'Seçimlər (hər sətirdə bir)', 'type' => 'lines', 'col' => 12],
        ['name' => 'categories', 'label' => 'Kateqoriyalar', 'type' => 'multiselect', 'sync' => 'categories', 'col' => 12],
    ];

    /**
     * Filter inputs for one category; loaded when the product form's category
     * changes. Prefills a product's saved values when product_id is supplied.
     */
    public function form(Request $request)
    {
        $categoryId = $request->integer('category_id') ?: null;
        $productId = $request->integer('product_id') ?: null;

        $filters = $categoryId
            ? Filter::query()
                ->whereHas('categories', fn ($q) => $q->where('categories.id', $categoryId))
                ->orderBy('id')
                ->get()
            : collect();

        $values = $productId
            ? ProductFilter::query()->where('product_id', $productId)->pluck('value', 'filter_id')->all()
            : [];

        return view('admin.pages.products._filters', [
            'filters' => $filters,
            'productFilters' => $values,
        ]);
    }

    protected function resolveOptions(array $field): array
    {
        if ($field['name'] === 'categories') {
            $options = [];
            foreach (Category::query()->orderBy('id')->get() as $category) {
                $options[$category->id] = admin_label($category, 'name', '#'.$category->id);
            }

            return $options;
        }

        return parent::resolveOptions($field);
    }
}
