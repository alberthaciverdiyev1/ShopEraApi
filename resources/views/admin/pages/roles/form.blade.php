@extends('admin.layouts.app')

@section('content')
    @php $isEdit = ! is_null($role); $current = $isEdit ? $role->permissions->pluck('id')->all() : old('permissions', []); @endphp
    <form method="POST" action="{{ $isEdit ? route('admin.roles.update', $role->id) : route('admin.roles.store') }}"
          class="mx-auto max-w-3xl space-y-5 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        @csrf @if ($isEdit) @method('PUT') @endif

        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Rol adı</label>
            <input type="text" name="name" value="{{ old('name', $role?->name) }}" required class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
            @error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">İcazələr</p>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                @foreach ($permissions as $group => $items)
                    <div class="rounded-xl border border-gray-100 p-3">
                        <p class="mb-2 text-xs font-bold uppercase text-gray-400">{{ $group }}</p>
                        <div class="space-y-1">
                            @foreach ($items as $permission)
                                <label class="flex items-center gap-2 text-sm text-gray-600">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                                           @checked(in_array($permission->id, $current))
                                           class="h-4 w-4 rounded border-gray-300 text-brand-600">
                                    {{ $permission->name }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex justify-end gap-2 border-t border-gray-100 pt-4">
            <a href="{{ route('admin.roles.index') }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">Ləğv et</a>
            <button class="rounded-lg bg-brand-600 px-5 py-2 text-sm font-semibold text-white hover:bg-brand-700">Yadda saxla</button>
        </div>
    </form>
@endsection
