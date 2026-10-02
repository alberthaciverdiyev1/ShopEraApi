@php $inputClass = $inputClass ?? 'w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-brand-500'; @endphp
<select name="category_id" class="{{ $inputClass }}"
        hx-get="{{ route('admin.filters.form') }}" hx-target="#product-filters" hx-swap="innerHTML"
        hx-trigger="load, change" hx-include="this,[name='product_id']">
    @if ($parent)
        <option value="{{ $parent->id }}" @selected((string) $current === (string) $parent->id)>— Əsas: {{ admin_label($parent) }} —</option>
        @foreach ($children as $child)
            <option value="{{ $child->id }}" @selected((string) $current === (string) $child->id)>{{ admin_label($child) }}</option>
        @endforeach
        @if ($children->isEmpty())
            <option value="">(alt kateqoriya yoxdur)</option>
        @endif
    @else
        <option value="">Əvvəlcə kateqoriya seçin</option>
    @endif
</select>
