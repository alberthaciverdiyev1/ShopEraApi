<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Modules\Category\Entities\Category;
use Modules\Filter\Entities\Filter;
use Modules\Filter\Entities\FilterValue;

/**
 * Manage the dependent filter tree (category → filter → value → sub-value),
 * e.g. Telefonlar → Marka → Model → Yaddaş.
 */
class FilterAdminController extends AdminController
{
    protected string $title = 'Filtrlər';

    public function index(Request $request)
    {
        $this->requirePermission('update category');

        $categories = Category::query()->orderBy('id')->get();
        $categoryId = (int) $request->query('category_id', 0) ?: (int) $categories->first()?->id;

        $filters = Filter::query()
            ->where('category_id', $categoryId)
            ->orderBy('sort_order')->orderBy('id')
            ->with(['values'])
            ->get();

        return view('admin.pages.filters', [
            'title' => $this->title,
            'categories' => $categories,
            'categoryId' => $categoryId,
            'filters' => $filters,
        ]);
    }

    public function storeFilter(Request $request)
    {
        $this->requirePermission('update category');

        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:120'],
            'depends_on_filter_id' => ['nullable', 'exists:filters,id'],
            'required' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        Filter::query()->create([
            'category_id' => $data['category_id'],
            'title' => $this->translations($data['title']),
            'type' => 'select',
            'depends_on_filter_id' => $data['depends_on_filter_id'] ?? null,
            'required' => $request->boolean('required'),
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return back()->with('status', __('Filtr əlavə edildi.'));
    }

    public function updateFilter(Request $request, int $id)
    {
        $this->requirePermission('update category');

        $filter = Filter::query()->findOrFail($id);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'depends_on_filter_id' => ['nullable', 'exists:filters,id'],
            'required' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        // A filter cannot depend on itself.
        $depends = $data['depends_on_filter_id'] ?? null;
        if ((int) $depends === $filter->id) {
            $depends = null;
        }

        $filter->update([
            'title' => $this->translations($data['title']),
            'depends_on_filter_id' => $depends,
            'required' => $request->boolean('required'),
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return back()->with('status', __('Filtr yeniləndi.'));
    }

    public function destroyFilter(int $id)
    {
        $this->requirePermission('update category');

        Filter::query()->findOrFail($id)->delete();

        return back()->with('status', __('Filtr silindi.'));
    }

    public function storeValue(Request $request)
    {
        $this->requirePermission('update category');

        $data = $request->validate([
            'filter_id' => ['required', 'exists:filters,id'],
            'parent_value_id' => ['nullable', 'exists:filter_values,id'],
            'title' => ['required', 'string', 'max:120'],
        ]);

        FilterValue::query()->create([
            'filter_id' => $data['filter_id'],
            'parent_value_id' => $data['parent_value_id'] ?? null,
            'title' => $this->translations($data['title']),
        ]);

        return back()->with('status', __('Dəyər əlavə edildi.'));
    }

    public function destroyValue(int $id)
    {
        $this->requirePermission('update category');

        FilterValue::query()->findOrFail($id)->delete();

        return back()->with('status', __('Dəyər silindi.'));
    }

    private function translations(string $value): array
    {
        return array_fill_keys(['az', 'en', 'ru', 'tr'], $value);
    }
}
