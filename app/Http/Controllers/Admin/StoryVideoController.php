<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Product\Entities\ProductVideo;

class StoryVideoController extends AdminController
{
    protected string $title = 'Story videoları';

    public function index(Request $request)
    {
        $this->requirePermission('view products');

        $query = ProductVideo::query()->with('product.images')->latest('id');

        if ($request->filled('status')) {
            $request->query('status') === 'active'
                ? $query->where('is_story_hidden', false)
                : $query->where('is_story_hidden', true);
        }

        if (($term = trim((string) $request->query('q', ''))) !== '') {
            $query->where(function ($inner) use ($term) {
                $inner->where('video_path', 'like', "%{$term}%")
                    ->orWhere('product_id', $term)
                    ->orWhereHas('product', fn ($p) => $p->where('title->az', 'like', "%{$term}%"));
            });
        }

        $rows = $query->paginate(20)->withQueryString();

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

        ProductVideo::query()->findOrFail($id)->update([
            'is_story_hidden' => false,
            'story_expires_at' => now()->addDay(),
        ]);

        return back()->with('status', __('Story video aktivləşdirildi.'));
    }

    public function deactivate(int $id)
    {
        $this->requirePermission('update product');

        ProductVideo::query()->findOrFail($id)->update([
            'is_story_hidden' => true,
            'story_expires_at' => null,
        ]);

        return back()->with('status', __('Story video deaktiv edildi.'));
    }

    public function destroy(int $id)
    {
        $this->requirePermission('update product');

        $video = ProductVideo::query()->findOrFail($id);
        $raw = $video->getRawOriginal('video_path');

        if ($raw && ! Str::startsWith($raw, 'http')) {
            Storage::disk('public')->delete($raw);
        }

        $video->delete();

        return back()->with('status', __('Video silindi.'));
    }
}
