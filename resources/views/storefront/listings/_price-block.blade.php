{{-- Price, discount and the general stock, plus the standard-price switch
     that keeps one price for every colour and size. Shared by the main step
     (categories without variants) and the variant step. --}}
@php($hasSizes = count($sizeChoices) > 0)
<div class="elan-price-block">
    @if ($visible('price'))
        <div class="elan-grid">
            <label class="elan-field">
                <span>Qiymət (₼) {{ $required('price') ? '*' : '' }}</span>
                <input type="number" name="price" min="0" step="0.01" value="{{ old('price') }}"
                       {{ $required('price') ? 'required' : '' }}>
            </label>
            <label class="elan-field">
                <span>Endirimli qiymət (₼)</span>
                <input type="number" name="discount" min="0" step="0.01" value="{{ old('discount') }}"
                       placeholder="Endirim yoxdursa boş buraxın">
            </label>
        </div>

        @if ($hasSizes)
            <label class="elan-check-pill elan-standard-price">
                <input type="hidden" name="standard_price" value="0">
                <input type="checkbox" name="standard_price" value="1" data-sf-standard-price
                       @checked(old('standard_price', '1') === '1')>
                <span>Bütün rəng və ölçülər üçün standart qiymət</span>
            </label>
            <span class="elan-hint">Aktiv olduqda əsas qiymət bütün rəng və ölçülərə tətbiq olunur. Söndürsəniz, seçdiyiniz ölçülər üçün ayrı qiymət və endirim yaza bilərsiniz. Ümumi stok həmişə qüvvədədir.</span>
        @endif
    @endif

    <div class="elan-grid">
        <label class="elan-field">
            <span>Stok <span class="muted small">(ümumi, rəng və ölçüdən asılı olmayaraq)</span></span>
            <input type="number" name="stock_count" min="0" step="1" value="{{ old('stock_count', 1) }}"
                   placeholder="Məsələn: 10">
        </label>
    </div>
</div>
