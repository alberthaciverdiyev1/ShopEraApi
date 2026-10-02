<?php

namespace App\Http\Controllers\Admin;

use App\Support\TenantContext;
use Illuminate\Http\Request;
use Modules\Notification\Entities\Notification;
use Modules\Notification\Services\NotificationService;

class NotificationController extends AdminController
{
    protected string $title = 'Bildirişlər';

    public function __construct(private readonly NotificationService $service) {}

    public function index(Request $request)
    {
        $this->requirePermission('view notifications');

        $query = Notification::query()->with('users')->latest('id');

        if ($request->query('source', 'admin') !== 'all') {
            $query->where('source', 'admin');
        }

        if (($term = trim((string) $request->query('q', ''))) !== '') {
            $query->where(fn ($inner) => $inner->where('title', 'like', "%{$term}%")->orWhere('body', 'like', "%{$term}%"));
        }

        $rows = $query->paginate(20)->withQueryString();

        if ($this->isHtmx($request)) {
            return view('admin.pages.notifications._table', ['rows' => $rows]);
        }

        return view('admin.pages.notifications.index', [
            'title' => $this->title,
            'rows' => $rows,
            'filters' => $request->only(['q', 'source']),
        ]);
    }

    public function create()
    {
        return view('admin.pages.notifications.create', ['title' => 'Bildiriş göndər']);
    }

    public function store(Request $request)
    {
        $this->requirePermission('send notification');

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'url' => ['nullable', 'string', 'max:2048'],
            'all' => ['nullable', 'boolean'],
            'image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
        ]);

        $imageUrl = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store(TenantContext::storagePath('notifications'), 'public');
            $imageUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($path);
        }

        $this->service->addMultiple([
            'title' => $data['title'],
            'body' => $data['body'],
            'url' => $data['url'] ?? null,
            'image' => $imageUrl,
            'source' => 'admin',
        ], [], $request->boolean('all'));

        return redirect()->route('admin.notifications.index')->with('status', __('Bildiriş göndərildi.'));
    }

    public function destroy(int $id)
    {
        $this->requirePermission('view notifications');

        Notification::query()->findOrFail($id)->delete();

        return back()->with('status', __('Bildiriş silindi.'));
    }
}
