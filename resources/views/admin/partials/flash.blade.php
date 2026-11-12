@if (session('status'))
    <div class="mb-4 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-3.5 text-sm text-emerald-800 shadow-sm dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300" role="alert">
        <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white">
            @include('admin.partials.icon', ['name' => 'check', 'class' => 'h-3.5 w-3.5'])
        </span>
        <span class="flex-1 leading-relaxed">{{ session('status') }}</span>
        <button type="button" onclick="this.parentElement.remove()" class="rounded p-0.5 text-emerald-600/70 hover:bg-emerald-100 hover:text-emerald-700 dark:text-emerald-300/70 dark:hover:bg-emerald-500/20" aria-label="Bağla">
            @include('admin.partials.icon', ['name' => 'x', 'class' => 'h-4 w-4'])
        </button>
    </div>
@endif

@if ($errors->any() && ! request()->header('HX-Request'))
    <div class="mb-4 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 p-3.5 text-sm text-rose-800 shadow-sm dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300" role="alert">
        <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-rose-500 text-white">
            @include('admin.partials.icon', ['name' => 'x', 'class' => 'h-3.5 w-3.5'])
        </span>
        <div class="flex-1">
            <p class="font-semibold">Formu yadda saxlamaq mümkün olmadı</p>
            <ul class="mt-1 list-inside list-disc space-y-0.5 text-rose-700 dark:text-rose-300/90">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="rounded p-0.5 text-rose-600/70 hover:bg-rose-100 hover:text-rose-700 dark:text-rose-300/70 dark:hover:bg-rose-500/20" aria-label="Bağla">
            @include('admin.partials.icon', ['name' => 'x', 'class' => 'h-4 w-4'])
        </button>
    </div>
@endif
