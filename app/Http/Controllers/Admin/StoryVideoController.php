<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Modules\Product\Services\ProductService;

class StoryVideoController extends AdminController
{
    protected string $title = 'Story videoları';

    private function service(): ProductService
    {
        return app(ProductService::class);
    }

    public function index(Request $request)
    {
        $this->requirePermission('view products');

        $rows = $this->service()->storyVideoQuery($request)->paginate(20)->withQueryString();

        if ($this->isHtmx($request)) {
            return view('admin.pages.story-videos._table', ['rows' => $rows]);
        }

        return view('admin.pages.story-videos.index', [
            'title' => $this->title,
            'rows' => $rows,
            'filters' => $request->only(['q', 'status']),
        ]);
    }

    public function activate(int $id)
    {
        $this->requirePermission('update product');

        $this->service()->setStoryVideoActive($id, true);

        return back()->with('status', __('Story video aktivləşdirildi.'));
    }

    public function deactivate(int $id)
    {
        $this->requirePermission('update product');

        $this->service()->setStoryVideoActive($id, false);

        return back()->with('status', __('Story video deaktiv edildi.'));
    }

    public function destroy(int $id)
    {
        $this->requirePermission('update product');

        $this->service()->removeStoryVideo($id);

        return back()->with('status', __('Video silindi.'));
    }
}
