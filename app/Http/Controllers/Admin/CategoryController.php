<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
        ['name' => 'sort_order', 'label' => 'Sıra', 'type' => 'number', 'col' => 3],
        ['name' => 'is_active', 'label' => 'Aktivdir', 'type' => 'checkbox', 'col' => 3],
    ];

    /** Categories are shown as a tree, so paginate generously. */
    protected int $perPage = 200;

    /** Top-level categories per page when browsing the tree. */
    private const ROOT_PER_PAGE = 50;

    private function service(): CategoryService
    {
        return app(CategoryService::class);
    }

    public function index(Request $request)
    {
        $nodes = $this->treeNodesFor($request);

        if ($this->isHtmx($request)) {
            return view($this->tableView(), ['nodes' => $nodes, 'route' => $this->route]);
        }

        return view('admin.pages.categories.index', [
            'title' => $this->title,
            'searchable' => true,
            'filters' => $request->only(['q']),
            'nodes' => $nodes,
            'route' => $this->route,
        ]);
    }

    /**
     * Tree nodes for the listing. A search term is shown as a flat, paginated
     * list of matches; browsing paginates top-level categories but always pulls
     * in their full subtree, so pagination can never split a parent from its
     * children.
     *
     * @return array<int,array{category:Category,depth:int,chain:string,hasChildren:bool}>
     */
    private function treeNodesFor(Request $request): array
    {
        $term = trim((string) $request->query('q', ''));

        if ($term !== '') {
            $rows = $this->listingQuery($request)->paginate($this->perPage)->withQueryString();

            return $this->service()->treeNodes($rows);
        }

        $roots = Category::query()
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(self::ROOT_PER_PAGE)
            ->withQueryString();

        $items = collect($roots->items());

        if ($items->isNotEmpty()) {
            $items = $items->merge($this->descendantsOf($items->pluck('id')->all()));
        }

        return $this->service()->treeNodes($items);
    }

    /**
     * All descendants of the given category ids, breadth-first so parents are
     * always collected before their children.
     *
     * @param  array<int,int>  $parentIds
     * @return Collection<int,Category>
     */
    private function descendantsOf(array $parentIds): Collection
    {
        $all = collect();
        $ids = $parentIds;

        while ($ids !== []) {
            $children = Category::query()
                ->whereIn('parent_id', $ids)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            if ($children->isEmpty()) {
                break;
            }

            $all = $all->merge($children);
            $ids = $children->pluck('id')->all();
        }

        return $all;
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
