@if (session('status'))
    <div class="mb-4 rounded-lg border border-green-300 bg-green-50 p-4 text-sm text-green-800" role="alert">{{ session('status') }}</div>
@endif
@if ($errors->any() && ! request()->header('HX-Request'))
    <div class="mb-4 rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-800" role="alert">
        <ul class="list-inside list-disc space-y-1">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif
