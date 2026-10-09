<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Modules\Category\Entities\Category;
use Modules\Category\Services\CategoryService;

class CategoryController extends ResourceController
{
    protected string $title = 'Kateqoriyalar';

    protected string $model = Category::class;

    protected string $route = 'admin.categories';

    protected string $storagePath = 'categories';

    protected array $searchable = ['name->az', 'name->en', 'name->ru', 'name->tr'];

    protected array $columns = [
        ['key' => 'image', 'label' => 'Şəkil', 'type' => 'image'],
        ['key' => 'name', 'label' => 'Ad', 'type' => 'translatable'],
        ['key' => 'sort_order', 'label' => 'Sıra', 'type' => 'number'],
        ['key' => 'is_active', 'label' => 'Status', 'type' => 'boolean'],
    ];

    protected array $fields = [
        ['name' => 'name', 'label' => 'Ad', 'type' => 'translatable_text', 'rules' => ['required', 'max:255'], 'col' => 6],
        ['name' => 'parent_id', 'label' => 'Üst kateqoriya', 'type' => 'select', 'col' => 6],
        ['name' => 'image', 'label' => 'Şəkil', 'type' => 'image', 'path' => 'categories', 'col' => 6],
        ['name' => 'background_color', 'label' => 'Kartın arxa fon rəngi', 'type' => 'color', 'col' => 6],
        ['name' => 'sort_order', 'label' => 'Sıra', 'type' => 'number', 'col' => 3],
        ['name' => 'is_active', 'label' => 'Aktivdir', 'type' => 'checkbox', 'col' => 3],
        ['name' => 'needs_brand', 'label' => 'Elan verməkdə marka tələb olunsun', 'type' => 'checkbox', 'col' => 12],
        ['name' => 'show_on_home', 'label' => 'Ana səhifədə göstər', 'type' => 'checkbox', 'col' => 12],
    ];

    /** Categories are shown as a tree, so paginate generously. */
    protected int $perPage = 200;

    private function service(): CategoryService
    {
        return app(CategoryService::class);
    }

    public function index(Request $request)
    {
        $rows = $this->listingQuery($request)->paginate($this->perPage)->withQueryString();

        if ($this->isHtmx($request)) {
            return view($this->tableView(), $this->tableData($rows));
        }

        return view('admin.pages.categories.index', array_merge($this->tableData($rows), [
            'title' => $this->title,
            'searchable' => true,
            'filters' => $request->only(['q']),
        ]));
    }

    /** Grouped ordering so children immediately follow their parent. */
    protected function listingQuery(Request $request): Builder
    {
        return $this->service()->adminQuery($request, $this->locales());
    }

    protected function tableView(): string
    {
        return 'admin.pages.categories._table';
    }

    protected function tableData($rows): array
    {
        return [
            'nodes' => $this->service()->treeNodes($rows),
            'route' => $this->route,
        ];
    }

    /** Subcategory options for a chosen parent (used by the product form). */
    public function children(Request $request)
    {
        [$parent, $children] = $this->service()->childrenFor($request->integer('parent_id') ?: null);

        return view('admin.pages.products._subcategories', [
            'parent' => $parent,
            'children' => $children,
            'current' => $request->input('current'),
        ]);
    }

    protected function columnMaps(): array
    {
        return ['parent_id' => $this->service()->parentOptions()];
    }

    protected function resolveOptions(array $field): array
    {
        if ($field['name'] === 'parent_id') {
            return $this->service()->parentOptions();
        }

        return parent::resolveOptions($field);
    }
}
