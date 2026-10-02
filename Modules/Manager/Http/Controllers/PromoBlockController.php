<?php

namespace Modules\Manager\Http\Controllers;

use Modules\Manager\Entities\PromoBlock;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;

class PromoBlockController extends Controller
{
    public function index()
    {
        return view('manager::promo-blocks.index', [
            'title' => 'Promo bloklar',
            'blocks' => PromoBlock::query()->orderBy('type')->orderBy('sort_order')->get(),
        ]);
    }

    public function create()
    {
        return view('manager::promo-blocks.form', ['title' => 'Yeni promo blok', 'block' => null]);
    }

    public function store(Request $request)
    {
        PromoBlock::query()->create($this->validated($request));

        return redirect()->route('manager.promo-blocks.index')->with('status', __('Blok yaradıldı.'));
    }

    public function edit(PromoBlock $promo_block)
    {
        return view('manager::promo-blocks.form', ['title' => 'Bloğu redaktə et', 'block' => $promo_block]);
    }

    public function update(Request $request, PromoBlock $promo_block)
    {
        $promo_block->update($this->validated($request, $promo_block));

        return back()->with('status', __('Blok yeniləndi.'));
    }

    public function destroy(PromoBlock $promo_block)
    {
        $promo_block->delete();

        return redirect()->route('manager.promo-blocks.index')->with('status', __('Blok silindi.'));
    }

    private function validated(Request $request, ?PromoBlock $block = null): array
    {
        $data = $request->validate([
            'type' => ['required', 'in:offer,ad'],
            'title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'button_text' => ['nullable', 'string', 'max:100'],
            'url' => ['nullable', 'string', 'max:255'],
            'badge' => ['nullable', 'string', 'max:64'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
            'image' => ['nullable', 'image', 'max:5120'],
        ]);

        unset($data['image']);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('promo', 'public');
        }

        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
