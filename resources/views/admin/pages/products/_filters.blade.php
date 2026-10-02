<div class="grid grid-cols-2 gap-3">
    @forelse ($filters as $filter)
        @php
            $opts = is_array($filter->options) ? $filter->options : [];
            $val = old("filters.{$filter->id}", ($productFilters ?? [])[$filter->id] ?? '');
        @endphp
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase text-gray-400">{{ $filter->title }}</label>
            @if (! empty($opts))
                <select name="filters[{{ $filter->id }}]" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                    <option value="">—</option>
                    @foreach ($opts as $opt)
                        <option value="{{ $opt }}" @selected((string) $val === (string) $opt)>{{ $opt }}</option>
                    @endforeach
                </select>
            @else
                <input type="text" name="filters[{{ $filter->id }}]" value="{{ $val }}" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
            @endif
        </div>
    @empty
        <p class="col-span-2 text-sm text-gray-400">Bu kateqoriya üçün filtr yoxdur.</p>
    @endforelse
</div>
