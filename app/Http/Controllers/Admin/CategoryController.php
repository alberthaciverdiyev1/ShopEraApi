<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\AbstractPaginator;
use App\Support\Features;
use Modules\Category\Entities\Category;

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
        ['name' => 'sort_order', 'label' => 'Sıra', 'type' => 'number', 'col' => 3],
        ['name' => 'is_active', 'label' => 'Aktivdir', 'type' => 'checkbox', 'col' => 3],
    ];

    /** Categories are shown as a tree, so paginate generously. */
    protected int $perPage = 200;

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
        $query = Category::query();

        if (($term = trim((string) $request->query('q', ''))) !== '') {
            $escaped = addcslashes($term, '%_\\');

            return $query->where(function (Builder $inner) use ($escaped) {
                foreach (['az', 'en', 'ru', 'tr'] as $locale) {
                    $inner->orWhere("name->{$locale}", 'like', "%{$escaped}%");
                }
            })->orderBy('id');
        }

        return $query->orderByRaw('COALESCE(parent_id, id)')->orderBy('id');
    }

    protected function tableView(): string
    {
        return 'admin.pages.categories._table';
    }

    protected function tableData($rows): array
    {
        return [
            'nodes' => $this->buildTree($rows),
            'route' => $this->route,
        ];
    }

    /** Flatten the rows into a depth-annotated list so children sit under parents. */
    private function buildTree($rows): array
    {
        $items = $rows instanceof AbstractPaginator ? $rows->getCollection() : collect($rows);
        $byParent = $items->groupBy(fn (Category $category) => $category->parent_id ?? 0);
        $withChildren = $items->pluck('parent_id')->filter()->unique()->flip();

        $nodes = [];
        $walk = function (int $parentId, int $depth, string $chain) use (&$walk, &$nodes, $byParent, $withChildren) {
            foreach ($byParent->get($parentId, collect()) as $category) {
                $nodes[] = [
                    'category' => $category,
                    'depth' => $depth,
                    'chain' => trim($chain),
                    'hasChildren' => $withChildren->has($category->id) || ($byParent->get($category->id, collect())->isNotEmpty()),
                ];
                $walk((int) $category->id, $depth + 1, $chain.' '.$category->id);
            }
        };
        $walk(0, 0, '');

        return $nodes;
    }

    /** Subcategory options for a chosen parent (used by the product form). */
    public function children(Request $request)
    {
        $parentId = $request->integer('parent_id') ?: null;
        $parent = $parentId ? Category::query()->find($parentId) : null;
        $children = $parentId
            ? Category::query()->where('parent_id', $parentId)->orderBy('id')->get()
            : collect();

        return view('admin.pages.products._subcategories', [
            'parent' => $parent,
            'children' => $children,
            'current' => $request->input('current'),
        ]);
    }

    protected function columnMaps(): array
    {
        $map = [];
        foreach (Category::query()->orderBy('id')->get() as $category) {
            $map[$category->id] = admin_label($category, 'name', '#'.$category->id);
        }

        return ['parent_id' => $map];
    }

    protected function resolveOptions(array $field): array
    {
        if ($field['name'] === 'parent_id') {
            $options = ['' => '— Ana kateqoriya —'];
            foreach (Category::query()->orderBy('id')->get() as $category) {
                $options[$category->id] = admin_label($category, 'name', '#'.$category->id);
            }

            return $options;
        }

        return parent::resolveOptions($field);
    }
}
