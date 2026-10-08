<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Modules\Category\Entities\Category;
use Modules\Marketplace\Entities\CategoryField;
use Modules\Marketplace\Support\ListingFields;

/**
 * Per-category listing form schema: choose which fields a category shows on
 * the listing form and whether they are required.
 */
class CategoryFieldController extends AdminController
{
    protected string $title = 'Kateqoriya sahələri';

    public function index(Request $request)
    {
        $this->requirePermission('update category');

        $categories = Category::query()->orderBy('id')->get();
        $categoryId = (int) $request->query('category_id', 0) ?: (int) $categories->first()?->id;

        return view('admin.pages.category-fields', [
            'title' => $this->title,
            'categories' => $categories,
            'categoryId' => $categoryId,
            'fields' => ListingFields::FIELDS,
            'schema' => ListingFields::forCategory($categoryId ?: null),
        ]);
    }

    public function update(Request $request)
    {
        $this->requirePermission('update category');

        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
        ]);

        $categoryId = (int) $data['category_id'];

        foreach (ListingFields::FIELDS as $field) {
            $visible = $request->boolean("fields.$field.visible");
            $required = $request->boolean("fields.$field.required") && $visible;

            CategoryField::query()->updateOrCreate(
                ['category_id' => $categoryId, 'field' => $field],
                ['is_visible' => $visible, 'is_required' => $required]
            );
        }

        return back()->with('status', __('Kateqoriya sahələri yeniləndi.'));
    }
}
