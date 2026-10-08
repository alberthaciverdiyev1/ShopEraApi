<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Modules\Marketplace\Entities\ListingReport;

class ListingReportController extends AdminController
{
    protected string $title = 'Şikayətlər';

    public function index(Request $request)
    {
        $this->requirePermission('update product');

        $reports = ListingReport::query()
            ->with(['product', 'user'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.pages.listing-reports', [
            'title' => $this->title,
            'reports' => $reports,
            'status' => $request->query('status'),
        ]);
    }

    public function resolve(int $id)
    {
        $this->requirePermission('update product');

        ListingReport::query()->findOrFail($id)->update(['status' => 'resolved']);

        return back()->with('status', __('Şikayət həll edildi.'));
    }
}
