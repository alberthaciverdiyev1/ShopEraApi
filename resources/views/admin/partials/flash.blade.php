@if (session('status'))
    <div class="mb-4 flex items-center gap-3 rounded-lg border border-green-300 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-gray-800 dark:text-green-400" role="alert">
        @include('admin.partials.icon', ['name' => 'check', 'class' => 'h-5 w-5 shrink-0'])
        <span>{{ session('status') }}</span>
    </div>
@endif

@if ($errors->any() && ! request()->header('HX-Request'))
    <div class="mb-4 rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-gray-800 dark:text-red-400" role="alert">
        <ul class="list-inside list-disc space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
